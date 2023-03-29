<?php

namespace Igniter\Main\Traits;

use Igniter\Flame\Pagic\Model;
use Igniter\System\Classes\BaseComponent;
use Igniter\System\Classes\ComponentManager;

trait ComponentMaker
{
    /**
     * @var \Igniter\System\Classes\BaseComponent[]
     */
    public $components = [];

    public function loadComponent(string $name, BaseComponent $component, Model $template)
    {
        $this->components[$name] = $component;
    }

    public function makeComponent($componentName)
    {
        if (!$name = $this->resolveComponentName($componentName))
            return null;

        return resolve(ComponentManager::class)->makeComponent(
            $componentName,
            null,
            $this->settings['components'][$name]
        );
    }

    public function resolveComponentName($componentName)
    {
        $componentManager = resolve(ComponentManager::class);
        $componentName = $componentManager->resolve($componentName);

        foreach ($this->settings['components'] ?? [] as $name => $values) {
            $result = $name;
            if ($name == $componentName)
                return $result;

            $parts = explode(' ', $name);
            if (count($parts) > 1) {
                $name = trim($parts[0]);
                if ($name == $componentName)
                    return $result;
            }

            $name = $componentManager->resolve($name);
            if ($name == $componentName)
                return $result;
        }

        return false;
    }
}