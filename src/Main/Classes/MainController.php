<?php

namespace Igniter\Main\Classes;

use Igniter\Admin\Facades\AdminHelper;
use Igniter\Flame\Exception\AjaxException;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Exception\ValidationException;
use Igniter\Flame\Flash\Facades\Flash;
use Igniter\Flame\Pagic\Parsers\FileParser;
use Igniter\Flame\Pagic\Router;
use Igniter\Main\Components\BlankComponent;
use Igniter\Main\Helpers\MainHelper;
use Igniter\Main\Template\ComponentPartial;
use Igniter\Main\Template\Content;
use Igniter\Main\Template\Layout as LayoutTemplate;
use Igniter\Main\Template\Partial;
use Igniter\System\Classes\BaseComponent;
use Igniter\System\Classes\ComponentManager;
use Igniter\System\Helpers\ViewHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Main Controller Class
 */
class MainController extends Controller
{
    use \Igniter\Main\Traits\ComponentMaker;
    use \Igniter\System\Traits\AssetMaker;
    use \Igniter\Flame\Traits\EventEmitter;
    use \Igniter\Flame\Traits\ExtendableTrait;
    use \Igniter\Admin\Traits\ControllerUtils;

    /**
     * @var \Igniter\Main\Classes\Theme The main theme processed by the controller.
     */
    protected $theme;

    protected Router $router;

    /**
     * @var \Igniter\Main\Template\Code\LayoutCode The template object used by the layout.
     */
    protected $layoutObj;

    /**
     * @var \Igniter\Main\Template\Code\PageCode The template object used by the page.
     */
    protected $pageObj;

    /**
     * @var \Igniter\Main\Template\Layout The main layout template used by the page.
     */
    protected $layout;

    /**
     * @var \Igniter\Main\Template\Page The main page template being processed.
     */
    protected $page;

    /**
     * @var self Cache of this controller
     */
    protected static $controller;

    /**
     * @var string Contains the rendered page contents string.
     */
    protected $pageContents;

    /**
     * @var array A list of variables to pass to the page.
     */
    public $vars = [];

    /**
     * @var array A list of BaseComponent objects used on this page
     */
    public $components = [];

    /**
     * @var \Igniter\System\Classes\BaseComponent Object of the active component, used internally.
     */
    protected $componentContext;

    /**
     * @var string Body class property used for customising the layout on a controller basis.
     */
    public $bodyClass;

    /**
     * @var int Response status code
     */
    protected $statusCode = 200;

    /**
     * Class constructor
     *
     * @param null $theme
     *
     * @throws \Igniter\Flame\Exception\FlashException
     */
    public function __construct($theme = null)
    {
        $this->theme = $theme ?: resolve(ThemeManager::class)->getActiveTheme();
        $this->router = resolve(Router::class);

        self::$controller = $this;

        $this->definePaths();

        $this->extendableConstruct();

        $this->fireSystemEvent('main.controller.beforeConstructor', [$this]);
    }

    protected function initialize()
    {
        if (!$this->theme) {
            throw FlashException::error(lang('igniter::main.not_found.active_theme'));
        }

        $this->theme->loadThemeFile();
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

    public function remap($method, $parameters)
    {
        $this->fireSystemEvent('main.controller.beforeRemap');

        $url = request()->path();

        // If the page was not found or a page is hidden,
        // render the 404 page - either provided by the theme or the built-in one.
        throw_unless($page = request()->route('_file_'), NotFoundHttpException::class);

        // Loads the requested controller action
        $output = $this->runPage($page);

        // Extensibility
        if ($event = $this->fireEvent('controller.beforeResponse', [$url, $page, $output])) {
            return $event;
        }

        if (!is_string($output)) {
            return $output;
        }

        return Response::make($output, $this->statusCode);
    }

    public function runPage($page)
    {
        $this->page = $page;

        if (!$page->layout) {
            $layout = LayoutTemplate::initFallback($this->theme->getName());
        } elseif (($layout = LayoutTemplate::loadCached($this->theme->getName(), $page->layout)) === null) {
            throw FlashException::error(sprintf(
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
        if ($this->layoutObj) {
            $this->layoutObj->onInit();
        }

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
    public function pageCycle()
    {
        return $this->execPageCycle();
    }

    protected function execPageCycle()
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
        $this->initialize();

        return $this->remap($method, $parameters);
    }

    //
    //
    //

    /**
     * Returns the AJAX handler for the current request, if available.
     * @return string
     */
    public function getHandler()
    {
        if (Request::ajax() && $handler = Request::header('X-IGNITER-REQUEST-HANDLER')) {
            return trim($handler);
        }

        if ($handler = post('_handler')) {
            return trim($handler);
        }

        return null;
    }

    protected function processHandlers()
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
                throw FlashException::error(sprintf(Lang::get('igniter::main.not_found.ajax_handler'), $handler));
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
            $response['X_IGNITER_ERROR_FIELDS'] = $ex->getFields();
            $response['X_IGNITER_ERROR_MESSAGE'] = lang('igniter::admin.alert_form_error_message');

            throw new AjaxException($response);
        }
    }

    protected function runHandler($handler)
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
     * @return self Returns the controller object or null.
     */
    public static function getController()
    {
        return self::$controller;
    }

    /**
     * Returns the Layout object being processed by the controller.
     * @return \Igniter\Main\Template\Code\LayoutCode Returns the Layout object or null.
     */
    public function getLayoutObj()
    {
        return $this->layoutObj;
    }

    /**
     * Returns the current theme.
     * @return \Igniter\Main\Classes\Theme
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Returns the routing object.
     * @return \Igniter\Flame\Pagic\Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Returns the template page object being processed by the controller.
     * The object is not available on the early stages of the controller
     * initialization.
     * @return \Igniter\Main\Template\Page Returns the Page object or null.
     */
    public function getPage()
    {
        return $this->page;
    }

    //
    // Initialization
    //

    public function initTemplateObjects()
    {
        $this->layoutObj = FileParser::on($this->layout)->source($this->page, $this->layout, $this);
        $this->pageObj = FileParser::on($this->page)->source($this->page, $this->layout, $this);
    }

    protected function initializeComponents()
    {
        foreach ($this->layout->getComponents() as $component => $properties) {
            [$name, $alias] = strpos($component, ' ')
                ? explode(' ', $component)
                : [$component, $component];

            $this->addComponent($name, $alias, $properties, true);
        }

        foreach ($this->page->getComponents() as $component => $properties) {
            [$name, $alias] = strpos($component, ' ')
                ? explode(' ', $component)
                : [$component, $component];

            $this->addComponent($name, $alias, $properties);
        }

        // Extensibility
        $this->fireSystemEvent('main.layout.initializeComponents', [$this->layoutObj]);
    }

    //
    // Rendering
    //

    /**
     * Renders a requested page.
     * The framework uses this method internally.
     */
    public function renderPage()
    {
        $contents = $this->pageContents;

        // Extensibility
        if ($event = $this->fireSystemEvent('main.page.render', [$contents])) {
            return $event;
        }

        return $contents;
    }

    public function renderPartial($name, array $params = [], $throwException = true)
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
     * @return string
     * @throws \Igniter\Flame\Exception\FlashException
     */
    public function renderContent($name, array $params = [])
    {
        // Extensibility
        if ($event = $this->fireSystemEvent('main.page.beforeRenderContent', [$name])) {
            $content = $event;
        } // Load content from theme
        elseif (($content = Content::loadCached($this->theme->getName(), $name)) === null) {
            throw FlashException::error(sprintf(
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

    /**
     * Renders a requested component default partial.
     * This method is used internally.
     *
     * @param string $name The component to load.
     * @param array $params Parameter variables to pass to the view.
     * @param bool $throwException Throw an exception if the partial is not found.
     *
     * @return mixed Partial contents or false if not throwing an exception.
     * @throws \Igniter\Flame\Exception\FlashException
     */
    public function renderComponent($name, array $params = [], $throwException = true)
    {
        $alias = str_before($name, '::');

        $previousContext = $this->componentContext;
        if (!$componentObj = $this->findComponentByAlias($alias)) {
            $this->handleException(sprintf(lang('igniter::main.not_found.component'), $alias), $throwException);

            return false;
        }

        $componentObj->id = uniqid($alias);
        $this->componentContext = $componentObj;
        $componentObj->setProperties(array_merge($componentObj->getProperties(), $params));
        if ($result = $componentObj->onRender()) {
            return $result;
        }

        if (!str_contains($name, '::')) {
            $name .= '::'.$componentObj->defaultPartial;
        }

        $result = $this->renderPartial($name, [], false);
        $this->componentContext = $previousContext;

        return $result;
    }

    //
    // Component helpers
    //

    /**
     * Adds a component to the layout object
     *
     * @param mixed $name Component class name or short name
     * @param string $alias Alias to give the component
     * @param array $properties Component properties
     * @param bool $addToLayout
     *
     * @return \Igniter\System\Classes\BaseComponent Component object
     * @throws \Exception
     */
    public function addComponent($name, $alias, $properties = [], $addToLayout = false)
    {
        $codeObj = $addToLayout ? $this->layoutObj : $this->pageObj;
        $templateObj = $addToLayout ? $this->layout : $this->page;

        $manager = resolve(ComponentManager::class);
        $componentObj = $manager->makeComponent($name, $codeObj, $properties);

        $componentObj->alias = $alias;
        $this->vars[$alias] = $componentObj;
        $templateObj->loadedComponents[$alias] = $componentObj;

        $componentObj->initialize();

        return $componentObj;
    }

    public function hasComponent($alias)
    {
        if (!$componentObj = $this->findComponentByAlias($alias)) {
            return false;
        }

        if ($componentObj instanceof BlankComponent) {
            return false;
        }

        return true;
    }

    /**
     * Searches the layout components by an alias
     *
     * @return \Igniter\System\Classes\BaseComponent The component object, if found
     */
    public function findComponentByAlias($alias)
    {
        if (isset($this->page->loadedComponents[$alias])) {
            return $this->page->loadedComponents[$alias];
        }

        if (isset($this->layout->loadedComponents[$alias])) {
            return $this->layout->loadedComponents[$alias];
        }

        return null;
    }

    /**
     * Searches the layout components by an AJAX handler
     *
     * @param string $handler
     *
     * @return \Igniter\System\Classes\BaseComponent The component object, if found
     */
    public function findComponentByHandler($handler)
    {
        foreach ($this->layout->components as $component) {
            if ($component->methodExists($handler)) {
                return $component;
            }
        }

        return null;
    }

    /**
     * Searches the layout and page components by a partial file
     *
     * @param string $partial
     *
     * @return \Igniter\System\Classes\BaseComponent The component object, if found
     */
    public function findComponentByPartial($partial)
    {
        foreach ($this->page->components as $component) {
            if (ComponentPartial::check($component, $partial)) {
                return $component;
            }
        }

        foreach ($this->layout->components as $component) {
            if (ComponentPartial::check($component, $partial)) {
                return $component;
            }
        }

        return null;
    }

    public function setComponentContext(BaseComponent $component = null)
    {
        $this->componentContext = $component;
    }

    protected function loadComponentPartial($name, $throwException = true)
    {
        [$componentAlias, $partialName] = explode('::', $name);

        // Component alias not supplied
        if (!strlen($componentAlias)) {
            if (!is_null($this->componentContext)) {
                $componentObj = $this->componentContext;
            } elseif (($componentObj = $this->findComponentByPartial($partialName)) === null) {
                $this->handleException(sprintf(lang('igniter::main.not_found.partial'), $partialName), $throwException);

                return false;
            }
        } elseif (($componentObj = $this->findComponentByAlias($componentAlias)) === null) {
            $this->handleException(sprintf(lang('igniter::main.not_found.component'), $componentAlias), $throwException);

            return false;
        }

        $partial = null;
        $this->componentContext = $componentObj;

        // Check if the theme has an override
        if (strpos($partialName, '/') === false) {
            $partial = ComponentPartial::loadOverrideCached($this->theme, $componentObj->alias, $partialName);
        }

        // Check the component partial
        if ($partial === null) {
            $partial = ComponentPartial::loadCached($componentObj->getPath(), $partialName);
        }

        if ($partial === null) {
            $this->handleException(sprintf(lang('igniter::main.not_found.partial'), $name), $throwException);

            return false;
        }

        return $partial;
    }

    protected function loadPartial($name, $throwException = true)
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

    public function url($path = null, $params = [])
    {
        if (is_null($path)) {
            return $this->currentPageUrl($params);
        }

        return URL::to($path, $params);
    }

    public function pageUrl($path = null, $params = [])
    {
        if (is_null($path)) {
            return $this->currentPageUrl($params);
        }

        return MainHelper::pageUrl($path, $params);
    }

    public function currentPageUrl($params = [])
    {
        return $this->pageUrl($this->page->getBaseFileName(), $params);
    }

    public function themeUrl($url = null)
    {
        traceLog('themeUrl() is deprecated, use public_url() instead.');
    }

    public function param($name, $default = null)
    {
        return $this->router->getParameter($name, $default);
    }

    public function refresh()
    {
        return Redirect::back();
    }

    public function redirect($path, $status = 302, $headers = [], $secure = null)
    {
        return Redirect::to($path, $status, $headers, $secure);
    }

    public function redirectGuest($path, $status = 302, $headers = [], $secure = null)
    {
        return Redirect::guest($path, $status, $headers, $secure);
    }

    public function redirectIntended($path, $status = 302, $headers = [], $secure = null)
    {
        return Redirect::intended($path, $status, $headers, $secure);
    }

    public function redirectBack()
    {
        return Redirect::back();
    }

    protected function handleException($message, $throwException)
    {
        if ($throwException) {
            throw FlashException::error($message);
        }

        flash()->danger($message);
    }

    protected function bindLocationRouteParameter($params)
    {
        if (!App::bound('location')) {
            return $params;
        }

        if (isset($params['location'])) {
            return $params;
        }

        if (!$location = App::make('location')->current()) {
            $location = App::make('location')->getDefault();
        }

        $params['location'] = $location ? $location->permalink_slug : null;

        return $params;
    }
}
