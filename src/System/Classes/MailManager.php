<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Support\StringParser;
use Igniter\System\Models\MailPartial;
use Igniter\System\Models\MailTemplate;
use Igniter\System\Models\MailTheme;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class MailManager
{
    /** A cache of templates. */
    protected array $templateCache = [];

    /** Cache of registration callbacks. */
    protected array $callbacks = [];

    /** List of registered templates in the system */
    protected ?array $registeredTemplates = null;

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

    public function applyMailerConfigValues()
    {
        $config = App::make('config');
        $config->set('mail.default', setting('protocol', $config['mail.default']));
        $config->set('mail.from.name', setting('sender_name', $config['mail.from.name']));
        $config->set('mail.from.address', setting('sender_email', $config['mail.from.address']));

        switch (setting('protocol')) {
            case 'smtp':
                $config->set('mail.mailers.smtp.host', setting('smtp_host'));
                $config->set('mail.mailers.smtp.port', setting('smtp_port'));
                $config->set('mail.mailers.smtp.encryption', strlen(setting('smtp_encryption'))
                    ? setting('smtp_encryption') : null
                );
                $config->set('mail.mailers.smtp.username', strlen(setting('smtp_user'))
                    ? setting('smtp_user') : null
                );
                $config->set('mail.mailers.smtp.password', strlen(setting('smtp_pass'))
                    ? setting('smtp_pass') : null
                );
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
    public function render(string $content, array $data = []): string
    {
        if (!$content) {
            return '';
        }

        $html = $this->renderView($content, $data);

        return Markdown::parse($html->toHtml());
    }

    /**
     * Render the Markdown template into text.
     */
    public function renderText(?string $content, array $data = []): string|HtmlString
    {
        if (!$content) {
            return '';
        }

        $text = $this->renderView($content, $data);

        return new HtmlString(html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n\n", $text), ENT_QUOTES, 'UTF-8'));
    }

    public function renderTemplate(MailTemplate $template, array $data = []): string
    {
        $this->isRenderingHtml = true;

        $html = $this->render($template->body, $data);

        if ($template->layout) {
            $html = $this->renderView($template->layout->layout,
                [
                    'body' => $html,
                    'layout_css' => $template->layout->layout_css,
                    'custom_css' => MailTheme::renderCss(),
                ] + $data
            );
        }

        return $html;
    }

    public function renderTextTemplate(MailTemplate $template, array $data = []): string|HtmlString
    {
        $this->isRenderingHtml = false;

        $templateText = $template->plain_body;
        if (!strlen($template->plain_body)) {
            $templateText = $template->body;
        }

        $text = $this->renderText($templateText, $data);

        if ($template->layout) {
            $text = $this->renderView($template->layout->plain_layout,
                [
                    'body' => $text,
                ] + $data
            );
        }

        return $text;
    }

    public function renderView(string $content, array $data = []): HtmlString
    {
        $this->registerBladeDirectives();

        $content = Blade::render($content, $data);

        return new HtmlString((new StringParser)->parse($content, $data));
    }

    public function startPartial(string $code, array $params = [])
    {
        if (ob_start()) {
            $this->partialStack[] = $code;

            $currentPartial = count($this->partialStack) - 1;
            $this->partialData[$currentPartial] = $params;
        }
    }

    public function renderPartial(): string|HtmlString
    {
        $code = array_pop($this->partialStack);
        if (!$partial = MailPartial::findOrMakePartial($code)) {
            return '<!-- Missing partial: '.$code.' -->';
        }

        $currentPartial = count($this->partialStack);
        $params = $this->partialData[$currentPartial];
        $params['slot'] = new HtmlString(trim(ob_get_clean()));

        $content = $partial->text ?: $partial->html;
        $content = $this->isRenderingHtml ? $partial->html : $content;

        if (!strlen(trim($content))) {
            return '';
        }

        return $this->renderView($content, $params);
    }

    //
    // Registration
    //

    /**
     * Loads registered templates from extensions
     */
    public function loadRegisteredTemplates()
    {
        foreach ($this->callbacks as $callback) {
            $callback($this);
        }

        $extensions = resolve(ExtensionManager::class)->getExtensions();
        foreach ($extensions as $extensionObj) {
            $this->processRegistrationMethodValues($extensionObj, 'registerMailLayouts');
            $this->processRegistrationMethodValues($extensionObj, 'registerMailTemplates');
            $this->processRegistrationMethodValues($extensionObj, 'registerMailPartials');
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
    public function registerMailLayouts(array $definitions)
    {
        if (!$this->registeredLayouts) {
            $this->registeredLayouts = [];
        }

        $this->registeredLayouts = $definitions + $this->registeredLayouts;
    }

    /**
     * Registers mail views and manageable templates.
     */
    public function registerMailTemplates(array $definitions)
    {
        if (!$this->registeredTemplates) {
            $this->registeredTemplates = [];
        }

        $this->registeredTemplates = $definitions + $this->registeredTemplates;
    }

    /**
     * Registers mail views and manageable partials.
     */
    public function registerMailPartials(array $definitions)
    {
        if (!$this->registeredPartials) {
            $this->registeredPartials = [];
        }

        $this->registeredPartials = $definitions + $this->registeredPartials;
    }

    /**
     * Registers mail variables.
     */
    public function registerMailVariables(array $definitions)
    {
        if (!$this->registeredVariables) {
            $this->registeredVariables = [];
        }

        $this->registeredVariables = $definitions + $this->registeredVariables;
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
    public function registerCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
    }

    protected function registerBladeDirectives()
    {
        Blade::directive('partial', function($expression) {
            return "<?php resolve(\Igniter\System\Classes\MailManager::class)->startPartial({$expression}); ?>";
        });

        Blade::directive('endpartial', function() {
            return "<?php echo resolve(\Igniter\System\Classes\MailManager::class)->renderPartial(); ?>";
        });
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
