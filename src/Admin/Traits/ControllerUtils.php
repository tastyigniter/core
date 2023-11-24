<?php

namespace Igniter\Admin\Traits;

use Igniter\Flame\Support\RouterHelper;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ControllerUtils
{
    /**
     * @var string Page method name being called.
     */
    protected $action;

    /**
     * @var array Routed parameters.
     */
    protected $params;

    /**
     * @var array Default actions which cannot be called as actions.
     */
    public $hiddenActions = [
        'checkAction',
        'pageAction',
        'execPageAction',
        'handleError',
        'pageCycle',
    ];

    /**
     * @var array Array of actions available without authentication.
     */
    protected $publicActions = [];

    /**
     * @var array Controller specified methods which cannot be called as actions.
     */
    protected $guarded = [];

    protected function setRequiredProperties()
    {
        $slug = request()->route('slug');
        $segments = RouterHelper::segmentizeUrl(is_string($slug) ? $slug : '');

        // Apply $guarded methods to hidden actions
        $this->hiddenActions = array_merge($this->hiddenActions, $this->guarded);

        $this->action = $segments[0] ?? 'index';
        $this->params = array_slice($segments, 1);
    }

    public function checkAction($action)
    {
        if (!$methodExists = $this->handlerMethodExists($action)) {
            return false;
        }

        if (in_array(strtolower($action), array_map('strtolower', $this->hiddenActions))) {
            throw new NotFoundHttpException(sprintf(
                'Method [%s] is not allowed in the controller [%s]',
                $action, get_class($this)
            ));
        }

        if (method_exists($this, $action)) {
            $methodInfo = new \ReflectionMethod($this, $action);

            return $methodInfo->isPublic();
        }

        return $methodExists;
    }

    public function callAction($method, $parameters)
    {
        $this->action = $method == 'remap' ? $this->action : $method;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }

        if (!$this->checkAction($method)) {
            throw new NotFoundHttpException(sprintf(
                'Method [%s] is not found in the controller [%s]',
                $method, get_class($this)
            ));
        }

        if (method_exists($this, 'remap')) {
            return $this->remap($this->action, $this->params);
        }

        return $this->{$method}(...$parameters);
    }

    public function getClass()
    {
        return $this->class ?? get_class($this);
    }

    public function getAction()
    {
        return $this->action;
    }

    protected function runHandler($handler, $params = [], $action = null)
    {
        $pageHandler = $action.'_'.$handler;

        if ($this->handlerMethodExists($pageHandler)) {
            $result = call_user_func_array([$this, $pageHandler], array_values($params));

            return $result ?: true;
        }

        // Process page global handler (onSomething)
        if ($this->handlerMethodExists($handler)) {
            $result = call_user_func_array([$this, $handler], array_values($params));

            return $result ?: true;
        }
    }

    protected function handlerMethodExists($handler)
    {
        return method_exists($this, 'methodExists')
            ? $this->methodExists($handler)
            : method_exists($this, $handler);
    }

    //
    // Extendable
    //

    public function __get(string $name): mixed
    {
        return $this->extendableGet($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->extendableSet($name, $value);
    }

    public function __call($method, $parameters): mixed
    {
        return $this->extendableCall($method, $parameters);
    }

    public static function __callStatic(string $name, array $params): mixed
    {
        return self::extendableCallStatic($name, $params);
    }

    public static function extend(callable $callback): void
    {
        self::extendableExtendCallback($callback);
    }
}
