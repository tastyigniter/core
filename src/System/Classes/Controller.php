<?php

namespace Igniter\System\Classes;

use Closure;
use Igniter\Flame\Igniter;
use Igniter\Flame\Support\RouterHelper;
use Igniter\Flame\Traits\ExtendableTrait;
use Igniter\System\Facades\Assets;
use Illuminate\Routing\Controller as IlluminateController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

/**
 * This is the base controller for all pages.
 * All requests that are prefixed with the admin URI pattern
 * OR have not been handled by the router are sent here,
 * then the URL is passed to the app controller for processing.
 * For example,
 * Request URI              Find Controller In
 * /admin/(any)             `admin`, `location` or `system` app directory
 * /admin/acme/cod/(any)    `Acme.Cod` extension
 * /(any)                   `main` app directory
 * @see \Igniter\Admin\Classes\AdminController|\Igniter\Main\Classes\MainController  controller class
 * @deprecated No longer used, will be removed in v5.0.0
 */
class Controller extends IlluminateController
{
    use ExtendableTrait;

    public static $class;

    /**
     * @var string Allows early access to page action.
     */
    public static $action;

    /**
     * @var array Allows early access to page URI segments.
     */
    public static $segments;

    /**
     * Stores the requested controller so that the constructor is only run once
     *
     * @var array|null
     */
    protected $requestedCache;

    public function __construct()
    {
        $this->extendableConstruct();
    }

    /**
     * Get the middleware assigned to the controller.
     *
     * @return array
     */
    public function getMiddleware()
    {
        $this->pushRequestedControllerMiddleware();

        return $this->middleware;
    }

    /**
     * Extend this object properties upon construction.
     */
    public static function extend(Closure $callback)
    {
        self::extendableExtendCallback($callback);
    }

    /**
     * Finds and serves the request using the main controller.
     *
     * @param string $url Specifies the requested page URL.
     *
     * @return string Returns the processed page content.
     */
    public function run($url = '/')
    {
        if (!Igniter::hasDatabase()) {
            return Response::make(View::make('igniter.system::no_database'));
        }

        return App::make(\Igniter\Main\Classes\MainController::class)->remap($url);
    }

    /**
     * Finds and serves the request using the admin controller.
     *
     * @param string $url Specifies the requested page URL.
     * If the parameter is omitted, the dashboard URL used.
     *
     * @return string Returns the processed page content.
     */
    public function runAdmin($url = '/')
    {
        if (!Igniter::hasDatabase()) {
            return Response::make(View::make('igniter.system::no_database'));
        }

        if ($result = $this->locateController($url)) {
            return $result['controller']->initialize()->remap($result['action'], $result['segments']);
        }

        return App::make('Igniter\Admin\Classes\AdminController')->initialize()->remap('404', []);
    }

    /**
     * Combines JavaScript and StyleSheet assets.
     *
     * @param string $asset
     *
     * @return string
     */
    public function combineAssets($asset)
    {
        $parts = explode('-', $asset);
        $cacheKey = $parts[0];

        return Assets::combineGetContents($cacheKey);
    }

    protected function locateController($url)
    {
        if (isset($this->requestedCache)) {
            return $this->requestedCache;
        }

        $segments = RouterHelper::segmentizeUrl($url);

        // Look for a controller within the /app directory
        if (!$result = $this->locateControllerInApp($segments)) {
            // Look for a controller within the /extensions directory
            $result = $this->locateControllerInExtensions($segments);
        }

        return $this->requestedCache = $result;
    }

    /**
     * This method is used internally.
     * Finds a controller with a callable action method.
     *
     * @param string $controller Specifies a controller name to locate.
     * @param string|array $modules Specifies a list of modules to look in.
     * @param string|array $inPath Base path to search the class file.
     *
     * @return bool|\Igniter\Admin\Classes\AdminController|\Igniter\Main\Classes\MainController
     * Returns the backend controller object
     */
    protected function locateControllerInPath($controller, $modules)
    {
        is_array($modules) || $modules = [$modules];

        $controllerClass = null;
        foreach ($modules as $namespace) {
            $controller = studly_case(str_replace(['\\', '_'], ['/', ''], $controller));
            if (class_exists($class = $namespace.'Controllers\\'.$controller)) {
                $controllerClass = $class;
                break;
            }
        }

        if (!$controllerClass || !class_exists($controllerClass)) {
            return null;
        }

        $controllerObj = App::make($controllerClass);
        if ($controllerObj->checkAction(self::$action)) {
            return $controllerObj;
        }

        return false;
    }

    /**
     * Process the action name, since dashes are not supported in PHP methods.
     *
     * @param string $actionName
     *
     * @return string
     */
    protected function processAction($actionName)
    {
        if (strpos($actionName, '-') !== false) {
            return camel_case($actionName);
        }

        return $actionName;
    }

    protected function locateControllerInApp(array $segments)
    {
        $modules = [
            '\\Igniter\\Admin\\Http\\',
            '\\Igniter\\Main\\Http\\',
            '\\Igniter\\System\\Http\\',
        ];

        $controller = $segments[0] ?? 'dashboard';
        self::$action = $action = isset($segments[1]) ? $this->processAction($segments[1]) : 'index';
        self::$segments = $segments = array_slice($segments, 2);

        if ($controllerObj = $this->locateControllerInPath($controller, $modules)) {
            return [
                'controller' => $controllerObj,
                'action' => $action,
                'segments' => $segments,
            ];
        }
    }

    protected function locateControllerInExtensions($segments)
    {
        if (count($segments) >= 3) {
            [$author, $extension, $controller] = $segments;
            self::$action = $action = isset($segments[3]) ? $this->processAction($segments[3]) : 'index';
            self::$segments = $segments = array_slice($segments, 4);

            $extensionCode = sprintf('%s.%s', $author, $extension);
            $extension = resolve(ExtensionManager::class)->findExtension($extensionCode);
            if (!$extension || $extension->disabled) {
                return;
            }

            $namespace = array_get($extension->extensionMeta(), 'namespace');
            if ($controllerObj = $this->locateControllerInPath(
                $controller,
                ["\\{$namespace}", "\\{$namespace}Http\\"],
            )) {
                return [
                    'controller' => $controllerObj,
                    'action' => $action,
                    'segments' => $segments,
                ];
            }
        }
    }

    protected function pushRequestedControllerMiddleware()
    {
        if (!Igniter::runningInAdmin()) {
            return;
        }

        $pathParts = explode('/', request()->path());
        if (Igniter::adminUri()) {
            array_shift($pathParts);
        }

        $path = implode('/', $pathParts);
        if ($result = $this->locateController($path)) {
            // Collect controller middleware and insert middleware into pipeline
            collect($result['controller']->getMiddleware())->each(function($data) {
                $this->middleware($data['middleware'], $data['options']);
            });
        }
    }
}
