<?php

namespace Igniter\Main\Classes;

use Igniter\Admin\Helpers\AdminHelper;
use Igniter\Admin\Traits\ControllerUtils;
use Igniter\Flame\Exception\AjaxException;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Flash\Facades\Flash;
use Igniter\Flame\Pagic\Parsers\FileParser;
use Igniter\Flame\Pagic\Router;
use Igniter\Flame\Traits\EventEmitter;
use Igniter\Flame\Traits\ExtendableTrait;
use Igniter\Main\Helpers\MainHelper;
use Igniter\Main\Template\Code\LayoutCode;
use Igniter\Main\Template\Code\PageCode;
use Igniter\Main\Template\ComponentPartial;
use Igniter\Main\Template\Content;
use Igniter\Main\Template\Layout;
use Igniter\Main\Template\Layout as LayoutTemplate;
use Igniter\Main\Template\Page;
use Igniter\Main\Template\Partial;
use Igniter\Main\Traits\ComponentMaker;
use Igniter\System\Helpers\ViewHelper;
use Igniter\System\Traits\AssetMaker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This is the base controller for all frontend pages.
 */
class MainController extends Controller
{
    use AssetMaker;
    use ComponentMaker;
    use ControllerUtils;
    use EventEmitter;
    use ExtendableTrait;

    /** The main theme processed by the controller. */
    protected ?Theme $theme = null;

    protected Router $router;

    /** The template object used by the layout.*/
    protected ?LayoutCode $layoutObj = null;

    /** The template object used by the page.*/
    protected ?PageCode $pageObj = null;

    /** The main layout template used by the page.*/
    protected ?Layout $layout = null;

    /** The main page template being processed.*/
    protected ?Page $page = null;

    /** Cache of this controller */
    protected static ?self $controller = null;

    /** Contains the rendered page contents string. */
    protected ?string $pageContents = null;

    /** A list of variables to pass to the page. */
    public array $vars = [];

    /** Body class property used for customising the layout on a controller basis. */
    public ?string $bodyClass = null;

    /** Response status code */
    protected int $statusCode = 200;

    public function __construct(?Theme $theme = null)
    {
        $this->theme = $theme ?: resolve(ThemeManager::class)->getActiveTheme();
        $this->router = resolve(Router::class);

        self::$controller = $this;

        $this->definePaths();

        $this->extendableConstruct();

        $this->fireSystemEvent('main.controller.beforeConstructor', [$this]);
    }

    protected function definePaths()
    {
        if (!$this->theme) {
            return;
        }

        $this->assetPath[] = $this->theme->getAssetPath();
        if ($this->theme->hasParent() && $parent = $this->theme->getParent()) {
            $this->assetPath[] = $parent->getAssetPath();
        }
    }

    public function remap(string $method, array $parameters): mixed
    {
        if (!$this->theme) {
            throw new FlashException(lang('igniter::main.not_found.active_theme'));
        }

        $this->fireSystemEvent('main.controller.beforeRemap');

        $url = request()->path();

        $page = Event::fire('router.beforeRoute', [$url, $this->router], true);
        if (is_null($page)) {
            $page = request()->route('_file_');
        }

        // If the page was not found or a page is hidden,
        // render the 404 page - either provided by the theme or the built-in one.
        throw_unless($page, NotFoundHttpException::class);

        // Loads the requested controller action
        $output = $this->runPage($page);

        // Extensibility
        if ($event = $this->fireSystemEvent('main.controller.beforeResponse', [$url, $page, $output])) {
            return $event;
        }

        if (!is_string($output)) {
            return $output;
        }

        return Response::make($output, $this->statusCode);
    }

    public function runPage(Page $page): mixed
    {
        $this->page = $page;

        if (!$page->layout) {
            $layout = LayoutTemplate::initFallback($this->theme->getName());
        } elseif (($layout = LayoutTemplate::loadCached($this->theme->getName(), $page->layout)) === null) {
            throw new FlashException(sprintf(
                Lang::get('igniter::main.not_found.layout_name'), $page->layout
            ));
        }

        $this->layout = $layout;

        // The 'this' variable is reserved for default variables.
        $this->vars['this'] = [
            'page' => $this->page,
            'layout' => $this->layout,
            'theme' => $this->theme,
            'param' => $this->router->getParameters(),
            'controller' => $this,
            'session' => App::make('session'),
        ];

        // Initializes the custom layout and page objects.
        $this->initTemplateObjects();

        // Attach layout components matching the current URI segments
        $this->initializeComponents();

        // Give the layout and page an opportunity to participate
        // after components are initialized and before AJAX is handled.
        $this->layoutObj?->onInit();

        $this->pageObj->onInit();

        // Extensibility
        if ($event = $this->fireSystemEvent('main.page.init', [$page])) {
            return $event;
        }

        // Execute post handler and AJAX event
        if (($ajaxResponse = $this->processHandlers()) && $ajaxResponse !== true) {
            return $ajaxResponse;
        }

        // Loads the requested controller action
        if ($pageResponse = $this->execPageCycle()) {
            return $pageResponse;
        }

        if ($event = $this->fireSystemEvent('main.page.beforeRenderPage', [$page])) {
            $this->pageContents = $event;
        } else {
            // Render the page
            $this->pageContents = pagic()->renderSource($this->page, $this->vars);
        }

        // Render the layout
        return pagic()->renderSource($this->layout, $this->vars);
    }

    /**
     * Invokes the current page cycle without rendering the page,
     * used by AJAX handler that may rely on the logic inside the action.
     */
    public function pageCycle(): mixed
    {
        return $this->execPageCycle();
    }

    protected function execPageCycle(): mixed
    {
        if ($event = $this->fireSystemEvent('main.page.start')) {
            return $event;
        }

        // Run layout functions
        if ($this->layoutObj) {
            // Let the layout do stuff after components are initialized and before AJAX is handled.
            $response = (
                ($result = $this->layoutObj->onStart()) ||
                ($result = $this->layout->runComponents())
            ) ? $result : null;

            if ($response) {
                return $response;
            }
        }

        // Run page functions
        $response = (
            ($result = $this->pageObj->onStart()) ||
            ($result = $this->page->runComponents()) ||
            ($result = $this->pageObj->onEnd())
        ) ? $result : null;

        if ($response) {
            return $response;
        }

        // Run remaining layout functions
        if ($this->layoutObj) {
            $response = ($result = $this->layoutObj->onEnd()) ? $result : null;
        }

        // Extensibility
        if ($event = $this->fireSystemEvent('main.page.end')) {
            return $event;
        }

        return $response;
    }

    public function callAction($method, $parameters)
    {
        return $this->remap($method, $parameters);
    }

    //
    //
    //

    protected function initTemplateObjects()
    {
        $this->layoutObj = FileParser::on($this->layout)->source($this->page, $this->layout, $this);
        $this->pageObj = FileParser::on($this->page)->source($this->page, $this->layout, $this);
    }

    protected function processHandlers(): mixed
    {
        if (!$handler = AdminHelper::getAjaxHandler()) {
            return false;
        }

        try {
            AdminHelper::validateAjaxHandler($handler);

            $partials = AdminHelper::validateAjaxHandlerPartials();

            $response = [];

            // Process Components handler
            if (!$result = $this->runHandler($handler)) {
                throw new FlashException(sprintf(Lang::get('igniter::main.not_found.ajax_handler'), $handler));
            }

            foreach ($partials as $partial) {
                $response[$partial] = $this->renderPartial($partial);
            }

            if ($result instanceof RedirectResponse) {
                $response['X_IGNITER_REDIRECT'] = $result->getTargetUrl();
                $result = null;
            } elseif (Request::header('X-IGNITER-REQUEST-FLASH') && Flash::messages()->isNotEmpty()) {
                $response['X_IGNITER_FLASH_MESSAGES'] = Flash::all();
            }

            if (is_array($result)) {
                $response = array_merge($response, $result);
            } elseif (is_string($result)) {
                $response['result'] = $result;
            } elseif (is_object($result)) {
                return $result;
            }

            return Response::make($response, $this->statusCode);
        } catch (ValidationException $ex) {
            $response['X_IGNITER_ERROR_FIELDS'] = $ex->errors();
            $response['X_IGNITER_ERROR_MESSAGE'] = lang('igniter::admin.alert_form_error_message');

            throw new AjaxException($response);
        }
    }

    protected function runHandler(string $handler): mixed
    {
        if (strpos($handler, '::')) {
            [$componentName, $handlerName] = explode('::', $handler);

            $componentObj = $this->findComponentByAlias($componentName);

            if ($componentObj && $componentObj->methodExists($handlerName)) {
                $this->componentContext = $componentObj;
                $result = $componentObj->runEventHandler($handlerName);

                return $result ?: true;
            }
        } // Process page specific handler (index_onSomething)
        else {
            $pageHandler = $this->action.'_'.$handler;
            if ($this->methodExists($pageHandler)) {
                $result = call_user_func_array([$this, $pageHandler], array_values($this->params));

                return $result ?: true;
            }

            if (($componentObj = $this->findComponentByHandler($handler)) !== null) {
                $this->componentContext = $componentObj;
                $result = $componentObj->runEventHandler($handler);

                return $result ?: true;
            }
        }

        return false;
    }

    //
    // Getters
    //

    /**
     * Returns an existing instance of the controller.
     * If the controller doesn't exists, returns null.
     */
    public static function getController(): ?self
    {
        return self::$controller ?: new self;
    }

    /**
     * Returns the Layout object being processed by the controller.
     */
    public function getLayoutObj(): ?LayoutCode
    {
        return $this->layoutObj;
    }

    /**
     * Returns the Page object being processed by the controller.
     */
    public function getPageObj(): ?PageCode
    {
        return $this->pageObj;
    }

    /**
     * Returns the current theme.
     */
    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    /**
     * Returns the routing object.
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Returns the template page object being processed by the controller.
     * The object is not available on the early stages of the controller
     * initialization.
     */
    public function getPage(): ?Page
    {
        return $this->page;
    }

    /**
     * Returns the template layout object being processed by the controller.
     * The object is not available on the early stages of the controller
     * initialization.
     */
    public function getLayout(): ?Layout
    {
        return $this->layout;
    }

    //
    // Rendering
    //

    /**
     * Renders a requested page.
     * @internal The framework uses this method internally.
     */
    public function renderPage(): string
    {
        $contents = $this->pageContents;

        // Extensibility
        if ($event = $this->fireSystemEvent('main.page.render', [$contents])) {
            return $event;
        }

        return $contents;
    }

    public function renderPartial(string $name, array $params = [], bool $throwException = true): mixed
    {
        // Cache variables
        $vars = $this->vars;
        $this->vars = array_merge($this->vars, $params);

        // Alias @ symbol for ::
        if (starts_with($name, '@')) {
            $name = '::'.substr($name, 1);
        }

        // Extensibility
        if ($event = $this->fireSystemEvent('main.page.beforeRenderPartial', [$name])) {
            $partial = $event;
        } // Process Component partial
        elseif (str_contains($name, '::')) {
            if (($partial = $this->loadComponentPartial($name, $throwException)) === false) {
                return false;
            }

            // Set context for self access
            $this->vars['__SELF__'] = $this->componentContext;
        } // Process theme partial
        elseif (($partial = $this->loadPartial($name, $throwException)) === false) {
            return false;
        }

        // Render the partial
        $partialContent = pagic()->renderSource($partial, $this->vars);

        // Restore variables
        $this->vars = $vars;

        // Extensibility
        if ($event = $this->fireSystemEvent('main.page.renderPartial', [$name, &$partialContent])) {
            return $event;
        }

        return $partialContent;
    }

    /**
     * Renders a requested content file.
     *
     * @param string $name The content view to load.
     * @param array $params Parameter variables to pass to the view.
     *
     * @throws \Igniter\Flame\Exception\FlashException
     */
    public function renderContent(string $name, array $params = []): string
    {
        // Extensibility
        if ($event = $this->fireSystemEvent('main.page.beforeRenderContent', [$name])) {
            $content = $event;
        } // Load content from theme
        elseif (($content = Content::loadCached($this->theme->getName(), $name)) === null) {
            throw new FlashException(sprintf(
                Lang::get('igniter::main.not_found.content'), $name
            ));
        }

        $fileContent = $content->getMarkup();

        // Inject global view variables
        $globalVars = ViewHelper::getGlobalVars();
        if (!empty($globalVars)) {
            $params += $globalVars;
        }

        // Parse basic template variables
        if (!empty($params)) {
            $fileContent = parse_values($params, $fileContent);
        }

        // Extensibility
        if ($event = $this->fireSystemEvent('main.page.renderContent', [$name, &$fileContent])) {
            return $event;
        }

        return $fileContent;
    }

    public function hasPartial(string $name): bool
    {
        // Alias @ symbol for ::
        if (starts_with($name, '@')) {
            $name = '::'.substr($name, 1);
        }

        if (str_contains($name, '::')) {
            if (($partial = $this->loadComponentPartial($name, false)) === false) {
                return false;
            }
        } // Process theme partial
        elseif (($partial = $this->loadPartial($name, false)) === false) {
            return false;
        }

        return $partial instanceof Partial || $partial instanceof ComponentPartial;
    }

    protected function loadPartial($name, $throwException = true): Partial|false
    {
        if (($partial = Partial::loadCached($this->theme->getName(), $name)) === null) {
            $this->handleException(sprintf(lang('igniter::main.not_found.partial'), $name), $throwException);

            return false;
        }

        return $partial;
    }

    //
    // Helpers
    //

    public function url(?string $path = null, array $params = []): string
    {
        if (is_null($path)) {
            return $this->currentPageUrl($params);
        }

        return URL::to($path, $params);
    }

    public function pageUrl(?string $path = null, array $params = []): string
    {
        if (is_null($path)) {
            return $this->currentPageUrl($params);
        }

        return MainHelper::pageUrl($path, $params);
    }

    public function currentPageUrl(array $params = []): string
    {
        return $this->pageUrl($this->page->getBaseFileName(), $params);
    }

    public function themeUrl(?string $url = null)
    {
        traceLog('themeUrl() is deprecated, use public_url() instead.');
    }

    public function param(string $name, mixed $default = null): mixed
    {
        return $this->router->getParameter($name, $default);
    }

    public function refresh(): RedirectResponse
    {
        return Redirect::back();
    }

    public function redirect(string $path, int $status = 302, array $headers = [], ?bool $secure = null): RedirectResponse
    {
        return Redirect::to($path, $status, $headers, $secure);
    }

    public function redirectGuest(string $path, int $status = 302, array $headers = [], ?bool $secure = null): RedirectResponse
    {
        return Redirect::guest($path, $status, $headers, $secure);
    }

    public function redirectIntended(string $path, int $status = 302, array $headers = [], ?bool $secure = null): RedirectResponse
    {
        return Redirect::intended($path, $status, $headers, $secure);
    }

    public function redirectBack(): RedirectResponse
    {
        return Redirect::back();
    }

    protected function handleException(string $message, bool $throwException)
    {
        if ($throwException) {
            throw new FlashException($message);
        }

        flash()->danger($message);
    }
}
