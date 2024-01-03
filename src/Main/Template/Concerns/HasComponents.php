<?php

namespace Igniter\Main\Template\Concerns;

use Igniter\System\Classes\ComponentManager;

trait HasComponents
{
    public $loadedComponents = [];

    /**
     * Returns a component by its name.
     * This method is used only in the admin and for internal system needs when
     * the standard way to access components is not an option.
     *
     * @param string $componentName Specifies the component name.
     *
     * @return \Igniter\System\Classes\BaseComponent
     */
    public function getComponent($componentName)
    {
        if (!$this->hasComponent($componentName)) {
            return null;
        }

        return array_get($this->settings['components'], $componentName);
    }

    /**
     * Checks if the object has a component with the specified name.
     *
     * @param string $componentName Specifies the component name.
     *
     * @return mixed Return false or the full component name used on the page (it could include the alias).
     */
    public function hasComponent($componentName)
    {
        $components = $this->settings['components'] ?? [];

        return array_has(is_array($components) ? $components : [], $componentName);
    }

    public function updateComponent($alias, array $properties)
    {
        $attributes = $this->attributes;

        $newAlias = array_get($properties, 'alias');
        if ($newAlias && $newAlias !== $alias) {
            $attributes = array_replace_key($attributes, $alias, $newAlias);
            $alias = $newAlias;
        }

        $attributes['settings']['components'][$alias] = $properties;
        $this->attributes = $attributes;

        return $this->save();
    }

    public function sortComponents(array $priorities)
    {
        $components = array_sort(array_get($this->settings, 'components', []),
            function ($value, $key) use ($priorities) {
                return $priorities[$key] ?? 0;
            }
        );

        $this->attributes['settings']['components'] = $components;
    }

    public function getComponents()
    {
        return $this->settings['components'] ?? [];
    }

    //
    //
    //

    public function makeComponent($componentName)
    {
        if (!$name = $this->resolveComponentName($componentName)) {
            return null;
        }

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
            if ($name == $componentName) {
                return $result;
            }

            $parts = explode(' ', $name);
            if (count($parts) > 1) {
                $name = trim($parts[0]);
                if ($name == $componentName) {
                    return $result;
                }
            }

            $name = $componentManager->resolve($name);
            if ($name == $componentName) {
                return $result;
            }
        }

        return false;
    }

    public function runComponents()
    {
        foreach ($this->loadedComponents as $component) {
            if ($event = $component->fireEvent('component.beforeRun', [], true)) {
                return $event;
            }

            if ($result = $component->onRun()) {
                return $result;
            }

            if ($event = $component->fireEvent('component.run', [], true)) {
                return $event;
            }
        }
    }
}
