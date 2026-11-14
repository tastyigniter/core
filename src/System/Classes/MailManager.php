<?php

declare(strict_types=1);

namespace Igniter\System\Classes;

use Igniter\Flame\Pagic\TemplateSandbox;
use Igniter\Flame\Support\StringParser;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\MailBuilder;
use Igniter\System\Classes\MailBuilder\MailVariables;
use Igniter\System\Helpers\ViewHelper;
use Igniter\System\Models\MailLayout;
use Igniter\System\Models\MailPartial;
use Igniter\System\Models\MailTemplate;
use Igniter\System\Models\MailTheme;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\HtmlString;

class MailManager
{
    /** A cache of templates. */
    protected array $templateCache = [];

    /** Cache of registration callbacks. */
    protected array $callbacks = [];

    /** List of registered templates in the system */
    protected ?array $registeredTemplates = null;

    /** Variable groups each registered template may use, keyed by template code. */
    protected array $templateVariableGroups = [];

    /** The variable group every template, layout and partial may use. */
    public const string GLOBAL_VARIABLES = 'igniter::system.mail_variables.text_group_global';

    /** List of registered partials in the system */
    protected ?array $registeredPartials = null;

    /** List of registered layouts in the system */
    protected ?array $registeredLayouts = null;

    /** List of registered variables in the system */
    protected ?array $registeredVariables = null;

    /** Internal marker for rendering mode */
    protected bool $isRenderingHtml = false;

    /** The partials being rendered. */
    protected array $partialStack = [];

    /** The original data passed to the partial. */
    protected array $partialData = [];

    /** Theme CSS used instead of the saved mail theme, while previewing unsaved colours. */
    protected ?string $customCssOverride = null;

    public function applyMailerConfigValues(): void
    {
        $config = App::make('config');
        $config->set('mail.default', setting('protocol', $config['mail.default']));
        $config->set('mail.from.name', setting('sender_name', $config['mail.from.name']));
        $config->set('mail.from.address', setting('sender_email', $config['mail.from.address']));

        switch (setting('protocol')) {
            case 'smtp':
                $config->set('mail.mailers.smtp.host', setting('smtp_host'));
                $config->set('mail.mailers.smtp.port', setting('smtp_port'));
                $config->set('mail.mailers.smtp.encryption', setting('smtp_encryption') ?: null);
                $config->set('mail.mailers.smtp.username', setting('smtp_user') ?: null);
                $config->set('mail.mailers.smtp.password', setting('smtp_pass') ?: null);
                break;
            case 'mailgun':
                $config->set('services.mailgun.domain', setting('mailgun_domain'));
                $config->set('services.mailgun.secret', setting('mailgun_secret'));
                break;
            case 'postmark':
                $config->set('services.postmark.token', setting('postmark_token'));
                break;
            case 'ses':
                $config->set('services.ses.key', setting('ses_key'));
                $config->set('services.ses.secret', setting('ses_secret'));
                $config->set('services.ses.region', setting('ses_region'));
                break;
        }
    }

    public function getTemplate(string $code): MailTemplate
    {
        if (isset($this->templateCache[$code])) {
            $template = $this->templateCache[$code];
        } else {
            $this->templateCache[$code] = $template = MailTemplate::findOrMakeTemplate($code);
        }

        return $template;
    }

    //
    // Rendering
    //

    /**
     * Render the Markdown template into HTML.
     */
    public function render(string $content, array $data = []): HtmlString
    {
        $html = $this->renderView($content, $data);

        return Markdown::parse($html->toHtml());
    }

    /**
     * Render the Markdown template into text.
     */
    public function renderText(string $content, array $data = []): HtmlString
    {
        $text = $this->renderView($content, $data)->toHtml();

        return new HtmlString(html_entity_decode((string)preg_replace("/[\r\n]{2,}/", "\n\n", $text), ENT_QUOTES, 'UTF-8'));
    }

    public function renderTemplate(MailTemplate $template, array $data = []): HtmlString
    {
        $this->isRenderingHtml = true;

        $html = $this->render($template->body, $data);

        if ($template->layout) {
            return $this->renderView($template->layout->layout,
                [
                    'body' => $html->toHtml(),
                    // Layout CSS is rendered inside a style tag, it may not open another tag
                    'layout_css' => str_replace('<', '', (string)$template->layout->layout_css),
                    'custom_css' => $this->customCssOverride ?? MailTheme::renderCss(),
                    'mail_logo_url' => $this->resolveLogoUrl($data),
                ] + $data,
            );
        }

        return $html;
    }

    public function renderTextTemplate(MailTemplate $template, array $data = []): HtmlString
    {
        $this->isRenderingHtml = false;

        $templateText = $template->plain_body ?: $template->body;
        $text = $this->renderText($templateText, $data);

        if ($template->layout) {
            $text = $this->renderView((string)$template->layout->plain_layout, ['body' => $text->toHtml()] + $data);

            return new HtmlString(html_entity_decode($text->toHtml(), ENT_QUOTES, 'UTF-8'));
        }

        return $text;
    }

    /**
     * The URL of the mail logo: the mail logo setting, or the site logo.
     */
    public function resolveLogoUrl(array $data = []): string
    {
        $logo = setting('mail_logo') ?: ($data['site_logo'] ?? null);
        if (!is_string($logo) || $logo === '') {
            return '';
        }

        return (string)rescue(fn() => media_thumb($logo, ['height' => 90]), '', false);
    }

    /**
     * Renders the template subject. Subjects of builder templates are text
     * with variable tokens and are compiled before rendering.
     */
    public function renderSubject(MailTemplate $template, array $data = []): string
    {
        $subject = (string)$template->subject;
        if ($template->usesBuilder()) {
            $builder = resolve(MailBuilder::class);
            $subject = $builder->compileSubject($subject, $builder->makeContext(CompileContext::MODE_TEMPLATE, $template->code));
        }

        return html_entity_decode($this->renderView($subject, $data)->toHtml(), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Renders an unsaved template, for previews and test messages.
     *
     * @return array{subject: string, html: string, text: string}
     */
    public function renderDraft(MailTemplate $template, array $data = [], ?string $customCss = null): array
    {
        $this->customCssOverride = $customCss;

        try {
            return [
                'subject' => $this->renderSubject($template, $data),
                'html' => $this->renderTemplate($template, $data)->toHtml(),
                'text' => $this->renderTextTemplate($template, $data)->toHtml(),
            ];
        } finally {
            $this->customCssOverride = null;
        }
    }

    /**
     * Renders an unsaved layout around sample content.
     *
     * @return array{subject: string, html: string, text: string}
     */
    public function renderLayoutDraft(MailLayout $layout, array $data = [], ?string $customCss = null): array
    {
        $template = new MailTemplate;
        $template->subject = lang('igniter::system.mail_builder.text_sample_subject');
        $template->body = '<p>'.e(lang('igniter::system.mail_builder.text_sample_body')).'</p>';
        $template->plain_body = e(lang('igniter::system.mail_builder.text_sample_body'));
        $template->setRelation('layout', $layout);

        return $this->renderDraft($template, $data, $customCss);
    }

    /**
     * Renders unsaved partial content with sample parameters and slot.
     *
     * @return array{subject: string, html: string, text: string}
     */
    public function renderPartialDraft(string $html, string $text, array $data = [], ?string $customCss = null): array
    {
        $this->isRenderingHtml = true;
        $data['slot'] = new HtmlString(e(lang('igniter::system.mail_builder.text_sample_slot')));

        $content = $this->renderView($html, $data)->toHtml();
        $css = str_replace('<', '', $customCss ?? MailTheme::renderCss());

        $this->isRenderingHtml = false;
        $data['slot'] = new HtmlString(lang('igniter::system.mail_builder.text_sample_slot'));

        return [
            'subject' => '',
            'html' => '<html><head><meta charset="utf-8"><style>'.$css.'</style></head><body>'
                .'<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0"><tr><td class="content-cell">'
                .$content.'</td></tr></table></body></html>',
            'text' => html_entity_decode($this->renderView($text, $data)->toHtml(), ENT_QUOTES, 'UTF-8'),
        ];
    }

    /**
     * Sample values for every registered variable, used by previews and test
     * messages. Listeners of `system.mail.sampleData` may return values to merge.
     *
     * @param array<int, array{name: string, label?: string, sample?: string}> $params Partial parameters
     */
    public function sampleData(?string $templateCode = null, array $params = []): array
    {
        $variables = new MailVariables($this->listRegisteredVariables());

        $data = [];
        foreach ($variables->scalars() as $name => $label) {
            $data[$name] = '['.$label.']';
        }

        foreach ($variables->arrays() as $name => $array) {
            $data[$name] = [];
            foreach ([1, 2] as $row) {
                $item = [];
                foreach ($array['fields'] as $field => $definition) {
                    $item[$field] = match (true) {
                        str_contains($field, 'quantity') => $row,
                        str_contains($field, 'price'), str_contains($field, 'subtotal'), str_contains($field, 'value') => number_format($row * 9.5, 2),
                        str_contains($field, 'options') => '',
                        default => $definition['label'].' '.$row,
                    };
                }

                $data[$name][] = $item;
            }
        }

        $data = array_merge($data, ViewHelper::getGlobalVars());

        foreach ($params as $param) {
            $name = (string)($param['name'] ?? '');
            if ($name !== '') {
                $data[$name] = (string)($param['sample'] ?? '') ?: '['.($param['label'] ?? $name).']';
            }
        }

        foreach ((array)Event::dispatch('system.mail.sampleData', [$templateCode, $data]) as $result) {
            if (is_array($result)) {
                $data = array_merge($data, $result);
            }
        }

        return $data;
    }

    public function renderView(string $content, array $data = []): HtmlString
    {
        $this->registerBladeDirectives();

        $content = Blade::render($content, $data);

        return new HtmlString((new StringParser)->parse($content, $data));
    }

    public function startPartial(string $code, array $params = []): void
    {
        if (ob_start()) {
            $this->partialStack[] = $code;

            $currentPartial = count($this->partialStack) - 1;
            $this->partialData[$currentPartial] = $params;
        }
    }

    public function renderPartial(): HtmlString
    {
        $code = array_pop($this->partialStack);
        if (!$partial = MailPartial::findOrMakePartial($code)) {
            return new HtmlString('<!-- Missing partial: '.$code.' -->');
        }

        $currentPartial = count($this->partialStack);
        $params = $this->partialData[$currentPartial];
        $params['slot'] = new HtmlString(trim(ob_get_clean()));

        $content = $partial->text ?: $partial->html;
        $content = $this->isRenderingHtml ? $partial->html : $content;

        return $this->renderView($content, $params);
    }

    //
    // Registration
    //

    /**
     * Loads registered templates from extensions
     */
    public function loadRegisteredTemplates(): void
    {
        foreach ($this->callbacks as $callback) {
            $callback($this);
        }

        $extensions = resolve(ExtensionManager::class)->getExtensions();
        foreach ($extensions as $extensionObj) {
            $this->processRegistrationMethodValues($extensionObj, 'registerMailLayouts');
            $this->processRegistrationMethodValues($extensionObj, 'registerMailTemplates');
            $this->processRegistrationMethodValues($extensionObj, 'registerMailPartials');
            $this->processRegistrationMethodValues($extensionObj, 'registerMailVariables');
        }
    }

    /**
     * Returns a list of the registered layouts.
     */
    public function listRegisteredLayouts(): array
    {
        if (is_null($this->registeredLayouts)) {
            $this->loadRegisteredTemplates();
        }

        return $this->registeredLayouts;
    }

    /**
     * Returns a list of the registered templates.
     */
    public function listRegisteredTemplates(): array
    {
        if (is_null($this->registeredTemplates)) {
            $this->loadRegisteredTemplates();
        }

        return $this->registeredTemplates;
    }

    /**
     * Returns a list of the registered partials.
     */
    public function listRegisteredPartials(): array
    {
        if (is_null($this->registeredPartials)) {
            $this->loadRegisteredTemplates();
        }

        return $this->registeredPartials;
    }

    /**
     * Returns a list of the registered variables.
     */
    public function listRegisteredVariables(): array
    {
        if (is_null($this->registeredVariables)) {
            $this->loadRegisteredTemplates();
        }

        return $this->registeredVariables;
    }

    /**
     * Registers mail views and manageable layouts.
     */
    public function registerMailLayouts(array $definitions): void
    {
        if (!$this->registeredLayouts) {
            $this->registeredLayouts = [];
        }

        $this->registeredLayouts = $definitions + $this->registeredLayouts;
    }

    /**
     * Registers mail views and manageable templates.
     */
    public function registerMailTemplates(array $definitions): void
    {
        if (!$this->registeredTemplates) {
            $this->registeredTemplates = [];
        }

        $templates = [];
        foreach ($definitions as $code => $definition) {
            if (is_array($definition)) {
                $this->templateVariableGroups[$code] = array_values((array)($definition['variables'] ?? []));
                $definition = $definition['label'] ?? $code;
            }

            $templates[$code] = $definition;
        }

        $this->registeredTemplates = $templates + $this->registeredTemplates;
    }

    /**
     * The variable groups a template may use: the global group and the groups
     * the template was registered with.
     *
     * @return list<string>
     */
    public function getTemplateVariableGroups(?string $code): array
    {
        $this->listRegisteredTemplates();

        return array_values(array_unique(array_merge(
            [self::GLOBAL_VARIABLES],
            $this->templateVariableGroups[(string)$code] ?? [],
        )));
    }

    /**
     * Registers mail views and manageable partials.
     */
    public function registerMailPartials(array $definitions): void
    {
        if (!$this->registeredPartials) {
            $this->registeredPartials = [];
        }

        $this->registeredPartials = $definitions + $this->registeredPartials;
    }

    /**
     * Registers mail variables.
     */
    public function registerMailVariables(array $definitions): void
    {
        if (!$this->registeredVariables) {
            $this->registeredVariables = [];
        }

        $this->registeredVariables = $definitions + $this->registeredVariables;
    }

    /**
     * Registers additional safe functions for mail template validation.
     *
     * @param array<int, string> $functions
     */
    public function registerMailTemplateFunctions(array $functions): void
    {
        resolve(TemplateSandbox::class)->registerAllowedFunctions($functions);
    }

    /**
     * Registers additional safe object methods for mail template validation.
     *
     * @param array<int, string> $methods
     */
    public function registerMailTemplateMethods(array $methods): void
    {
        resolve(TemplateSandbox::class)->registerAllowedMethods($methods);
    }

    /**
     * Registers a callback function that defines templates.
     * The callback function should register templates by calling the manager's
     * registerMailTemplates() function. This instance is passed to the
     * callback function as an argument. Usage:
     * <pre>
     *   resolve(MailManager::class)->registerCallback(function($manager){
     *       $manager->registerMailTemplates([...]);
     *   });
     * </pre>
     *
     * @param callable $callback A callable function.
     */
    public function registerCallback(callable $callback): void
    {
        $this->callbacks[] = $callback;
    }

    protected function registerBladeDirectives()
    {
        Blade::directive('partial', fn(string $expression): string => sprintf('<?php resolve('.MailManager::class.'::class)->startPartial(%s); ?>', $expression));

        Blade::directive('endpartial', fn(): string => '<?php echo resolve('.MailManager::class.'::class)->renderPartial(); ?>');
    }

    protected function processRegistrationMethodValues(BaseExtension $extension, string $method)
    {
        if (!method_exists($extension, $method)) {
            return;
        }

        $results = $extension->$method();
        if (is_array($results)) {
            $this->$method($results);
        }
    }
}
