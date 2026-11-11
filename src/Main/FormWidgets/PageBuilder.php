<?php

declare(strict_types=1);

namespace Igniter\Main\FormWidgets;

use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Exception\FlashException;
use Igniter\Main\Classes\BaseBlock;
use Igniter\Main\Classes\BlockManager;
use Igniter\Main\Classes\PageBuilder as PageBuilderService;
use Igniter\Main\Classes\SectionLayouts;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\ThemePageLayout;
use Illuminate\Support\Facades\Validator;
use Override;

/**
 * Page builder form widget.
 *
 * Edits a page as a sortable list of sections. Each section has a column layout
 * and blocks are added inside the columns. The value is the sections array,
 * posted as a single JSON string.
 *
 * Usage:
 * <pre>
 *   'sections' => [
 *       'type' => 'pagebuilder',
 *       'themeCode' => 'igniter-orange',  // defaults to the active theme
 *       'pageCode' => 'home',             // used for required and page restricted blocks
 *   ]
 * </pre>
 */
class PageBuilder extends BaseFormWidget
{
    //
    // Configurable properties
    //

    public ?string $themeCode = null;

    public ?string $pageCode = null;

    //
    // Object properties
    //

    protected string $defaultAlias = 'pagebuilder';

    protected ?Theme $theme = null;

    #[Override]
    public function initialize(): void
    {
        $this->fillFromConfig([
            'themeCode',
            'pageCode',
        ]);

        $themeCode = $this->themeCode ?? Theme::getActiveCode();
        $theme = $themeCode ? resolve(ThemeManager::class)->findTheme($themeCode) : null;

        throw_unless($theme, new FlashException(sprintf(lang('igniter::main.builder.alert_theme_not_found'), $themeCode)));

        $this->theme = $theme;
    }

    #[Override]
    public function loadAssets(): void
    {
        $this->addCss('pagebuilder.css', 'pagebuilder-css');
        $this->addJs('pagebuilder.js', 'pagebuilder-js');
    }

    #[Override]
    public function render(): string
    {
        $this->prepareVars();
        $this->preloadFieldAssets();

        return $this->makePartial('pagebuilder/pagebuilder');
    }

    public function prepareVars(): void
    {
        $sections = $this->getSections();

        $this->vars['field'] = $this->formField;
        $this->vars['sections'] = $sections;
        $this->vars['editorConfig'] = $this->getEditorConfig($sections);
        $this->vars['layouts'] = $this->getLayoutOptions();
    }

    public function getTheme(): Theme
    {
        return $this->theme;
    }

    /**
     * Returns the normalized sections, including any required blocks.
     */
    public function getSections(): array
    {
        $value = $this->getLoadValue();

        // Nothing saved yet, start from the theme default layout for the page
        $sections = is_null($value) && $this->pageCode
            ? $this->getService()->defaultSections($this->theme, $this->pageCode)
            : $this->getService()->normalize($this->theme, $this->decodeValue($value), $this->pageCode);

        return $this->getService()->ensureRequiredBlocks($this->theme, $sections, $this->pageCode);
    }

    #[Override]
    public function getSaveValue(mixed $value): array
    {
        $sections = $this->getService()->normalize($this->theme, $this->decodeValue($value), $this->pageCode);

        return $this->getService()->ensureRequiredBlocks($this->theme, $sections, $this->pageCode);
    }

    //
    // Handlers
    //

    public function onLoadBlockForm(): string
    {
        $block = $this->findBlockOrFail((string)post('code'));
        $settings = array_merge($block->defaults(), $this->decodeValue(post('settings')));

        return $this->makePartial('pagebuilder/form', [
            'formTitle' => sprintf(lang('igniter::main.builder.text_edit_block'), $block->getName()),
            'formWidget' => $this->makeSettingsFormWidget('block', $block->defineFields(), $settings),
            'formHandler' => $this->getEventHandler('onValidateBlockForm'),
            'formCode' => $block->getCode(),
        ]);
    }

    public function onValidateBlockForm(): array
    {
        $block = $this->findBlockOrFail((string)post('code'));

        $formWidget = $this->makeSettingsFormWidget('block', $block->defineFields(), $block->defaults());
        $settings = array_merge($block->defaults(), $formWidget->getSaveData());

        Validator::make($settings, $block->validationRules(), [], $this->getFieldLabels($block->defineFields()))->validate();

        $settings = $block->sanitize($settings);

        return [
            'settings' => $settings,
            'summary' => $block->summary($settings),
        ];
    }

    public function onLoadSectionForm(): string
    {
        $settings = SectionLayouts::normalizeSettings($this->decodeValue(post('settings')));

        return $this->makePartial('pagebuilder/form', [
            'formTitle' => lang('igniter::main.builder.text_section_settings'),
            'formWidget' => $this->makeSettingsFormWidget('section', SectionLayouts::settingsFields(), $settings),
            'formHandler' => $this->getEventHandler('onValidateSectionForm'),
            'formCode' => null,
        ]);
    }

    public function onValidateSectionForm(): array
    {
        $formWidget = $this->makeSettingsFormWidget('section', SectionLayouts::settingsFields(), SectionLayouts::defaultSettings());

        return [
            'settings' => SectionLayouts::normalizeSettings($formWidget->getSaveData()),
        ];
    }

    //
    // Helpers
    //

    protected function getService(): PageBuilderService
    {
        return resolve(PageBuilderService::class);
    }

    /**
     * Data used by the editor script: available blocks, layouts, required blocks,
     * block summaries and translated messages.
     */
    protected function getEditorConfig(array $sections): array
    {
        $blocks = resolve(BlockManager::class)->listBlocks($this->theme, $this->pageCode);
        $required = $this->getService()->requiredBlocks($this->theme, $this->pageCode);

        $summaries = [];
        foreach ($sections as $section) {
            foreach ($section['columns'] as $column) {
                foreach ($column['blocks'] as $block) {
                    $summaries[$block['id']] = $blocks[$block['code']]->summary($block['settings']);
                }
            }
        }

        return [
            'alias' => $this->alias,
            'previewMode' => $this->previewMode,
            'blocks' => array_map(fn(BaseBlock $block): array => [
                'code' => $block->getCode(),
                'name' => $block->getName(),
                'description' => lang((string)$block->getDetails()['description']),
                'icon' => $block->getDetails()['icon'],
                'unique' => $block->isUnique(),
                'wide' => $block->isWide(),
                'hasFields' => $block->defineFields() !== [],
                'required' => in_array($block->getCode(), $required, true),
                'defaults' => $block->sanitize($block->defaults()),
            ], $blocks),
            'layouts' => $this->getLayoutOptions(),
            'defaultSectionSettings' => SectionLayouts::defaultSettings(),
            'wideSpan' => SectionLayouts::WIDE_SPAN,
            'summaries' => $summaries,
            'messages' => [
                'confirmRemoveBlock' => lang('igniter::main.builder.confirm_remove_block'),
                'confirmRemoveSection' => lang('igniter::main.builder.confirm_remove_section'),
                'confirmLayoutChange' => lang('igniter::main.builder.confirm_layout_change'),
                'alertWideBlock' => lang('igniter::main.builder.alert_wide_block'),
                'alertRequiredBlock' => lang('igniter::main.builder.alert_required_block'),
                'alertRequestFailed' => lang('igniter::main.builder.alert_request_failed'),
            ],
        ];
    }

    /**
     * @return array<string, array{code: string, label: string, columns: int, equal: bool, spans: list<int|null>}>
     */
    protected function getLayoutOptions(): array
    {
        $layouts = [];
        foreach (SectionLayouts::options() as $code => $label) {
            $layouts[$code] = [
                'code' => $code,
                'label' => $label,
                'columns' => SectionLayouts::columnCount($code),
                'equal' => SectionLayouts::isEqual($code),
                'spans' => SectionLayouts::spans($code),
            ];
        }

        return $layouts;
    }

    protected function findBlockOrFail(string $code): BaseBlock
    {
        $block = resolve(BlockManager::class)->findBlock($this->theme, $code, $this->pageCode);

        throw_unless($block, new FlashException(sprintf(lang('igniter::main.builder.alert_block_not_found'), $code)));

        return $block;
    }

    /**
     * Settings forms get a blank model of their own. Using the parent form model
     * would let extensions that extend that model's forms (e.g. adding tabbed
     * fields to every static page form) act on these small settings forms.
     */
    protected function makeSettingsFormWidget(string $context, array $fields, array $data): Form
    {
        /** @var Form $widget */
        $widget = $this->makeWidget(Form::class, [
            'alias' => $this->alias.ucfirst($context).'Form',
            'arrayName' => $this->alias.'Settings',
            'model' => new ThemePageLayout,
            'data' => $data,
            'fields' => $fields,
            'context' => $context,
            'previewMode' => $this->previewMode,
        ]);

        $widget->bindToController();

        return $widget;
    }

    /**
     * Block and section settings forms are loaded into a modal over AJAX, which
     * cannot add field widget scripts and styles to the page. Build one form
     * holding every field type in use so their assets load with the page.
     */
    protected function preloadFieldAssets(): void
    {
        if ($this->previewMode) {
            return;
        }

        $fields = [];
        $definitions = array_map(fn(BaseBlock $block): array => $block->defineFields(),
            resolve(BlockManager::class)->listBlocks($this->theme, $this->pageCode));
        $definitions[] = SectionLayouts::settingsFields();

        foreach ($definitions as $blockFields) {
            foreach ($blockFields as $field) {
                $type = $field['type'] ?? 'text';
                $fields['_preload_'.$type] ??= ['type' => $type];
            }
        }

        $this->makeSettingsFormWidget('assets', $fields, []);
    }

    protected function getFieldLabels(array $fields): array
    {
        return array_map(fn(array $field): string => lang((string)($field['label'] ?? '')), $fields);
    }

    /**
     * Accepts the sections/settings as an array or JSON string.
     */
    protected function decodeValue(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }
}
