<?php

namespace Igniter\System\Classes;

use Igniter\Admin\Classes\AdminController;
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
    use ExtensionTrait;
    use ViewMaker;
    use WidgetMaker;

    protected $controller;

    /** List of controller configuration */
    protected ?array $config = null;

    /** Properties that must exist in the controller using this action. */
    protected array $requiredProperties = [];

    public function __construct($controller = null)
    {
        /** @var AdminController $controller */
        $this->controller = $controller;

        // Add paths from the extension / module context
        $this->configPath = $this->controller->configPath ?? [];
        $this->partialPath = $this->controller->partialPath ?? [];

        foreach ($this->requiredProperties as $property) {
            if (!isset($controller->{$property})) {
                throw new \LogicException('Class '.$this->controller::class." must define property [$property] used by ".get_called_class());
            }
        }
    }

    /**
     * Sets the widget configuration values
     */
    public function setConfig(null|string|array $config, array $required = [])
    {
        $this->config = $this->makeConfig($config, $required);
    }

    /**
     * Get the widget configuration values.
     */
    public function getConfig(?string $name = null, mixed $default = null): mixed
    {
        if (is_null($name)) {
            return $this->config;
        }

        $nameArray = name_to_array($name);

        $fieldName = array_shift($nameArray);
        $result = $this->config[$fieldName] ?? $default;

        foreach ($nameArray as $key) {
            if (!is_array($result) || !array_key_exists($key, $result)) {
                return $default;
            }

            $result = $result[$key];
        }

        return $result;
    }

    /**
     * Protects a public method from being available as an controller method.
     */
    protected function hideAction(string|array $methodName)
    {
        if (!is_array($methodName)) {
            $methodName = [$methodName];
        }

        $this->controller->hiddenActions = array_merge($this->controller->hiddenActions, $methodName);
    }
}
