<?php

declare(strict_types=1);

namespace Igniter\System\FormWidgets;

use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\MailBuilder as MailBuilderService;
use Igniter\System\Classes\MailBuilder\MailImporter;
use Igniter\System\Classes\MailManager;
use Igniter\System\Models\MailLayout;
use Igniter\System\Models\MailPartial;
use Igniter\System\Models\MailTemplate;
use Igniter\System\Models\MailTheme;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Override;
use Throwable;

/**
 * Mail builder form widget.
 *
 * Edits a mail template, layout or partial as a list of blocks, with a live
 * preview, variable picker, theme colours and undo history. The value is the
 * blocks list, posted as a single JSON string. The record content is compiled
 * from the blocks when the record is saved.
 *
 * Usage:
 * <pre>
 *   'blocks' => [
 *       'type' => 'mailbuilder',
 *       'mode' => 'template', // template, layout or partial
 *   ]
 * </pre>
 */
class MailBuilder extends BaseFormWidget
{
    //
    // Configurable properties
    //

    public string $mode = CompileContext::MODE_TEMPLATE;

    //
    // Object properties
    //

    protected string $defaultAlias = 'mailbuilder';

    /** Notices from importing existing content. */
    protected array $notices = [];

    #[Override]
    public function initialize(): void
    {
        $this->fillFromConfig(['mode']);

        throw_unless(in_array($this->mode, CompileContext::MODES, true), new FlashException(
            sprintf('Invalid mail builder mode [%s].', $this->mode),
        ));
    }

    #[Override]
    public function loadAssets(): void
    {
        $this->addCss('mailbuilder.css', 'mailbuilder-css');
        $this->addJs('mailbuilder.js', 'mailbuilder-js');

        foreach ($this->builder()->getBlockManager()->listAssets($this->makeContext()) as $asset) {
            $this->addJs($asset);
        }
    }

    #[Override]
    public function render(): string
    {
        $this->prepareVars();

        return $this->makePartial('mailbuilder/mailbuilder');
    }

    public function prepareVars(): void
    {
        $blocks = $this->getBlocks();

        $this->vars['field'] = $this->formField;
        $this->vars['blocks'] = $blocks;
        $this->vars['editorConfig'] = $this->getEditorConfig();
    }

    /**
     * The blocks being edited. Records without blocks are imported from their
     * content, new records start with a starter set.
     */
    public function getBlocks(): array
    {
        $context = $this->makeContext();
        $value = $this->getLoadValue();

        if (!is_null($value)) {
            return $this->builder()->normalize($value, $context);
        }

        [$source, $view] = $this->getSourceContent();
        if (trim((string)$source) === '') {
            return $this->builder()->normalize($this->starterBlocks(), $context);
        }

        $result = resolve(MailImporter::class)->import((string)$source, $context, $view);
        $this->notices = $result['notices'];

        return $result['blocks'];
    }

    #[Override]
    public function getSaveValue(mixed $value): array
    {
        $context = $this->makeContext(params: $this->getPostedParams());
        $blocks = $this->builder()->normalize($value, $context);

        $this->builder()->validate($blocks, $context);

        $this->compileIntoModel($blocks, $context);

        return $blocks;
    }

    /**
     * Writes the compiled content to the record being saved. The models also
     * compile on save, this keeps the form save independent of model events.
     */
    protected function compileIntoModel(array $blocks, CompileContext $context): void
    {
        $html = $this->builder()->compileHtml($blocks, $context);
        $text = $this->builder()->compileText($blocks, $context);

        match (true) {
            $this->model instanceof MailTemplate => [$this->model->body, $this->model->plain_body] = [$html, $text],
            $this->model instanceof MailLayout => [$this->model->layout, $this->model->plain_layout] = [$html, $text],
            $this->model instanceof MailPartial => [$this->model->html, $this->model->text] = [$html, $text],
            default => null,
        };
    }

    public function getEditorConfig(): array
    {
        $context = $this->makeContext(params: $this->getModelParams());
        $blockManager = $this->builder()->getBlockManager();

        $blocks = [];
        foreach ($blockManager->listBlocks($context) as $block) {
            $blocks[] = $block->toConfig() + ['hidden' => $block->getDetails()['category'] === 'hidden'];
        }

        $names = [];
        foreach ($blockManager->listBlocks() as $code => $block) {
            $names[$code] = $block->getName();
        }

        return [
            'mode' => $this->mode,
            'previewMode' => $this->previewMode,
            'handlers' => [
                'preview' => $this->getEventHandler('onPreview'),
                'sendTest' => $this->getEventHandler('onSendTest'),
                'saveTheme' => $this->getEventHandler('onSaveTheme'),
            ],
            'blocks' => $blocks,
            'blockNames' => $names,
            'variables' => $this->getVariableGroups($context),
            'theme' => $this->getThemeFields(),
            'notices' => $this->notices,
            'subject' => $this->model instanceof MailTemplate ? (string)$this->model->subject : null,
            'testEmail' => $this->controller->getUser()?->email,
            'lang' => $this->getLangStrings(),
        ];
    }

    //
    // Handlers
    //

    /**
     * Compiles and renders the posted draft with sample data.
     */
    public function onPreview(): array
    {
        $context = $this->makeContext(params: $this->getPostedParams(), preview: true);
        $blocks = $this->builder()->normalize(post('blocks'), $context);

        $errors = [];

        try {
            $this->builder()->validate($blocks, $context);
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
        }

        try {
            $result = $this->renderDraft($blocks, $context, $this->getPostedTheme());
        } catch (Throwable $throwable) {
            return ['errors' => $errors, 'error' => $throwable->getMessage()];
        }

        $summaries = [];
        foreach ($blocks as $item) {
            $block = $this->builder()->getBlockManager()->findBlock((string)$item['code']);
            $summaries[$item['id']] = $block instanceof BaseMailBlock ? $block->summary((array)$item['settings']) : '';
        }

        return $result + ['errors' => $errors, 'summaries' => $summaries];
    }

    /**
     * Sends the posted draft, rendered with sample data, to an email address.
     */
    public function onSendTest(): array
    {
        $email = (string)post('email');
        Validator::make(['email' => $email], ['email' => ['required', 'email:filter']], [], [
            'email' => lang('igniter::system.mail_builder.label_test_email'),
        ])->validate();

        $context = $this->makeContext(params: $this->getPostedParams());
        $blocks = $this->builder()->normalize(post('blocks'), $context);
        $this->builder()->validate($blocks, $context);

        $result = $this->renderDraft($blocks, $context, $this->getPostedTheme());
        $subject = $result['subject'] !== '' ? $result['subject'] : lang('igniter::system.mail_builder.text_test_subject');

        Mail::html($result['html'], function($message) use ($email, $subject, $result) {
            $message->to($email)->subject($subject)->text($result['text']);
        });

        return ['message' => sprintf(lang('igniter::system.mail_builder.alert_test_sent'), $email)];
    }

    /**
     * Saves the mail theme colours. The theme applies to every mail.
     */
    public function onSaveTheme(): array
    {
        $values = $this->getPostedTheme(strict: true);

        MailTheme::set($values);
        MailTheme::instance()->resetCache();

        return ['message' => lang('igniter::system.mail_builder.alert_theme_saved'), 'theme' => MailTheme::makeCssVars()];
    }

    //
    // Helpers
    //

    public function makeContext(array $params = [], bool $preview = false): CompileContext
    {
        return $this->builder()->makeContext($this->mode, $this->getModelCode(), $params, $preview);
    }

    protected function renderDraft(array $blocks, CompileContext $context, array $theme): array
    {
        $html = $this->builder()->compileHtml($blocks, $context);
        $text = $this->builder()->compileText($blocks, $context);

        $manager = resolve(MailManager::class);
        $data = $manager->sampleData($this->getModelCode(), $context->params);
        foreach ($blocks as $item) {
            $block = $this->builder()->getBlockManager()->findBlock((string)$item['code']);
            if ($block instanceof BaseMailBlock) {
                $data = array_merge($data, $block->preview((array)$item['settings']));
            }
        }

        $css = MailTheme::renderCss($theme);

        if ($this->mode === CompileContext::MODE_LAYOUT) {
            $layout = new MailLayout;
            $layout->code = $this->getModelCode();
            $layout->layout = $html;
            $layout->plain_layout = $text;
            $layout->layout_css = (string)post('layout_css', $this->model->layout_css ?? '');

            return $manager->renderLayoutDraft($layout, $data, $css);
        }

        if ($this->mode === CompileContext::MODE_PARTIAL) {
            return $manager->renderPartialDraft($html, $text, $data, $css);
        }

        $template = new MailTemplate;
        $template->code = $this->getModelCode();
        $template->subject = (string)post('subject', $this->model->subject ?? '');
        $template->body = $html;
        $template->plain_body = $text;
        $template->blocks = $blocks;
        $template->layout_id = post('layout_id') ? (int)post('layout_id') : ($this->model->layout_id ?? null);

        return $manager->renderDraft($template, $data, $css);
    }

    /**
     * The existing content to import and the registered view it comes from,
     * when it has not been customized.
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function getSourceContent(): array
    {
        $model = $this->model;

        return match (true) {
            $model instanceof MailTemplate => [$model->body, $model->exists && !$model->is_custom ? $model->code : null],
            $model instanceof MailLayout => [$model->layout, $model->exists && !$model->is_locked ? $model->getRegisteredView() : null],
            $model instanceof MailPartial => [$model->html, $model->exists && !$model->is_custom ? $model->getRegisteredView() : null],
            default => [null, null],
        };
    }

    protected function starterBlocks(): array
    {
        $defaults = fn(string $code): array => [
            'code' => $code,
            'settings' => $this->builder()->getBlockManager()->findBlock($code)?->defaults() ?? [],
        ];

        return match ($this->mode) {
            CompileContext::MODE_LAYOUT => [$defaults('header'), $defaults('content_slot'), $defaults('footer')],
            CompileContext::MODE_PARTIAL => [$defaults('slot')],
            default => [$defaults('heading'), $defaults('text'), $defaults('button')],
        };
    }

    protected function getModelCode(): ?string
    {
        $code = $this->model->code ?? null;

        return is_string($code) && $code !== '' ? $code : null;
    }

    protected function getModelParams(): array
    {
        return $this->model instanceof MailPartial ? $this->model->getParamDefinitions() : [];
    }

    /**
     * Partial parameters from the posted form, falling back to the saved ones.
     */
    protected function getPostedParams(): array
    {
        if (!$this->model instanceof MailPartial) {
            return [];
        }

        $params = post('params');
        if (is_string($params)) {
            $params = json_decode($params, true);
        }

        if (!is_array($params)) {
            $params = array_get((array)post($this->formField->arrayName), 'params');
        }

        if (!is_array($params)) {
            return $this->getModelParams();
        }

        $partial = new MailPartial;
        $partial->params = array_values($params);

        return $partial->getParamDefinitions();
    }

    /**
     * Theme colours from the request. With strict, invalid values are a validation error.
     */
    protected function getPostedTheme(bool $strict = false): array
    {
        $theme = post('theme');
        if (is_string($theme)) {
            $theme = json_decode($theme, true);
        }

        $values = [];
        $errors = [];
        foreach (MailTheme::getCssVars() as $name => $default) {
            if (!is_array($theme) || !array_key_exists($name, $theme)) {
                continue;
            }

            if (MailTheme::isColor($theme[$name])) {
                $values[$name] = strtolower((string)$theme[$name]);
            } else {
                $errors['theme.'.$name] = [lang('igniter::system.mail_builder.alert_invalid_color')];
            }
        }

        if ($strict && $errors) {
            throw ValidationException::withMessages($errors);
        }

        return $values;
    }

    protected function getVariableGroups(CompileContext $context): array
    {
        $groups = $context->variables->groups();

        if ($this->mode === CompileContext::MODE_PARTIAL) {
            array_unshift($groups, [
                'code' => 'params',
                'label' => lang('igniter::system.mail_builder.text_partial_params'),
                'variables' => array_map(fn(array $param): array => [
                    'type' => 'scalar',
                    'name' => $param['name'],
                    'token' => '{{ $'.$param['name'].' }}',
                    'label' => $param['label'],
                ], $context->params),
            ]);
        }

        return $groups;
    }

    protected function getThemeFields(): array
    {
        $config = $this->loadThemeConfig();
        $values = MailTheme::makeCssVars();
        $defaults = MailTheme::getCssVars();

        $fields = [];
        foreach ((array)array_get($config, 'form.fields', []) as $name => $field) {
            if (!array_key_exists($name, $defaults)) {
                continue;
            }

            $fields[] = [
                'name' => $name,
                'label' => lang((string)($field['label'] ?? $name)),
                'group' => lang((string)($field['group'] ?? '')),
                'value' => $values[str_replace('_', '-', $name)],
                'default' => $defaults[$name],
            ];
        }

        return $fields;
    }

    /**
     * The mail theme field definitions, see resources/models/system/mail_themes.php.
     */
    protected function loadThemeConfig(): array
    {
        return (array)File::getRequire(File::symbolizePath('igniter::models/system/mail_themes.php'));
    }

    protected function getLangStrings(): array
    {
        $keys = [
            'add_block', 'settings', 'variables', 'theme', 'undo', 'redo', 'desktop', 'mobile',
            'plain_text', 'send_test', 'send', 'cancel', 'duplicate', 'delete', 'move_up', 'move_down',
            'no_selection', 'no_settings', 'search_variables', 'insert_variable_hint', 'select_text_field',
            'theme_site_wide', 'save_theme', 'reset_theme', 'unavailable_block', 'read_only_block', 'unsaved_changes',
            'preview_failed', 'required_block', 'select_param', 'loop_hint', 'add_row', 'add_column',
            'remove_row', 'remove_column', 'column_label', 'cell', 'drag', 'align', 'add_block_hint', 'empty_block',
            'subject_preview', 'choose_media', 'insert', 'insert_image', 'media_unavailable', 'select_image', 'no_variables',
            'always_visible', 'reset_template', 'confirm_reset',
            'test_email', 'sending', 'saving',
        ];

        $strings = [];
        foreach ($keys as $key) {
            $strings[$key] = lang('igniter::system.mail_builder.js_'.$key);
        }

        return $strings;
    }

    protected function builder(): MailBuilderService
    {
        return resolve(MailBuilderService::class);
    }
}
