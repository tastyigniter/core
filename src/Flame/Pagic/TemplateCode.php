<?php

namespace Igniter\Flame\Pagic;

use ArrayAccess;
use Igniter\Flame\Support\Extendable;

/**
 * Parent class for PHP classes created for layout and page code sections.
 */
class TemplateCode extends Extendable implements ArrayAccess
{
    /**
     * @var \Main\Template\Page The current page
     */
    public $page;

    /**
     * @var \Main\Template\Layout The current layout
     */
    public $layout;

    /**
     * @var \Main\Classes\MainController The template controller
     */
    public $controller;

    /**
     * Creates the object instance.
     *
     * @param \Main\Template\Page $page The template page.
     * @param \Main\Template\Layout $layout The template layout.
     * @param \Main\Classes\MainController $controller The template controller.
     */
    public function __construct($page, $layout, $controller)
    {
        $this->page = $page;
        $this->layout = $layout;
        $this->controller = $controller;

        parent::__construct();
    }

    /**
     * This event is triggered when all components are initialized and before AJAX is handled.
     * The layout's onInit method triggers before the page's onInit method.
     */
    public function onInit()
    {
    }

    /**
     * This event is triggered in the beginning of the execution cycle.
     * The layout's onStart method triggers before the page's onStart method.
     */
    public function onStart()
    {
    }

    /**
     * This event is triggered in the end of the execution cycle, but before the page is displayed.
     * The layout's onEnd method triggers after the page's onEnd method.
     */
    public function onEnd()
    {
    }

    /**
     * ArrayAccess implementation
     *
     * @param $offset
     * @param $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->controller->vars[$offset] = $value;
    }

    /**
     * ArrayAccess implementation
     *
     * @param $offset
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->controller->vars[$offset]);
    }

    /**
     * ArrayAccess implementation
     *
     * @param $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->controller->vars[$offset]);
    }

    /**
     * ArrayAccess implementation
     *
     * @param $offset
     *
     * @return null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return isset($this->controller->vars[$offset]) ? $this->controller->vars[$offset] : null;
    }

    /**
     * Dynamically handle calls into the controller instance.
     *
     * @param string $name
     * @param array $params
     *
     * @return mixed
     */
    public function __call($name, $params)
    {
        if ($this->methodExists($name)) {
            return call_user_func_array([$this, $name], $params);
        }

        if (method_exists($this->page, $name))
            return call_user_func_array([$this->page, $name], $params);

        return call_user_func_array([$this->controller, $name], $params);
    }

    /**
     * This object is referenced as $this->page in System\Classes\BaseComponent,
     * so to avoid $this->page->page this method will proxy there. This is also
     * used as a helper for accessing controller variables/components easier
     * in the page code, eg. $this->foo instead of $this['foo']
     *
     * @param string $name
     *
     * @return void
     */
    public function __get($name)
    {
        if (isset($this->page->components[$name]) || isset($this->layout->components[$name])) {
            return $this[$name];
        }

        if (($value = $this->page->{$name}) !== null) {
            return $value;
        }

        if (array_key_exists($name, $this->controller->vars)) {
            return $this[$name];
        }

        return null;
    }

    /**
     * This will set a property on the Page object.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->page->{$name} = $value;
    }

    /**
     * This will check if a property isset on the CMS Page object.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->page->{$name});
    }
}
