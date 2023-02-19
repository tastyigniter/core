<?php

namespace Igniter\System\Classes;

use Exception;
use Igniter\Admin\Traits\WidgetMaker;
use Igniter\Flame\Traits\ExtensionTrait;
use Igniter\System\Traits\ConfigMaker;
use Igniter\System\Traits\ViewMaker;

/**
 * Controller Action base Class
 */
class ControllerAction
{
    use ConfigMaker;
    use ViewMaker;
    use WidgetMaker;
    use ExtensionTrait;

    /**
     * @var \Igniter\Admin\Classes\AdminController|\Illuminate\Routing\Controller Reference to the controller associated to this action
     */
    protected $controller;

    /**
     * @var array List of controller configuration
     */
    protected $config;

    /**
     * @var array Properties that must exist in the controller using this action.
     */
    protected $requiredProperties = [];

    /**
     * ControllerAction constructor.
     *
     * @param \Illuminate\Routing\Controller $controller
     *
     * @throws \Exception
     */
    public function __construct($controller = null)
    {
        if ($controller !== null)
            $this->controller = $controller;

        // Add paths from the extension / module context
        $this->configPath = $this->controller->configPath;
        $this->partialPath = $this->controller->partialPath;

        foreach ($this->requiredProperties as $property) {
            if (!isset($controller->{$property})) {
                throw new Exception('Class '.get_class($controller)." must define property [{$property}] used by ".get_called_class());
            }
        }
    }

    /**
     * Sets the widget configuration values
     *
     * @param string|array $config
     * @param array $required Required config items
     */
    public function setConfig($config, $required = [])
    {
        $this->config = $this->makeConfig($config, $required);
    }

    /**
     * Get the widget configuration values.
     *
     * @param string $name Config name, supports array names like "field[key]"
     * @param mixed $default Default value if nothing is found
     *
     * @return mixed
     */
    public function getConfig($name = null, $default = null)
    {
        if (is_null($name))
            return $this->config;

        $nameArray = name_to_array($name);

        $fieldName = array_shift($nameArray);
        $result = isset($this->config[$fieldName]) ? $this->config[$fieldName] : $default;

        foreach ($nameArray as $key) {
            if (!is_array($result) || !array_key_exists($key, $result))
                return $default;

            $result = $result[$key];
        }

        return $result;
    }

    /**
     * Protects a public method from being available as an controller method.
     *
     * @param $methodName
     */
    protected function hideAction($methodName)
    {
        if (!is_array($methodName)) {
            $methodName = [$methodName];
        }

        $this->controller->hiddenActions = array_merge($this->controller->hiddenActions, $methodName);
    }
}
