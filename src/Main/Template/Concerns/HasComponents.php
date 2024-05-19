<?php

namespace Igniter\Main\Template\Concerns;

use Igniter\System\Classes\BaseComponent;

trait HasComponents
{
    public array $loadedComponents = [];

    public array $loadedConfigurableComponents = [];

    /**
     * Returns a component by its name.
     * This method is used only in the admin and for internal system needs when
     * the standard way to access components is not an option.
     */
    public function getComponent(string $componentName): ?BaseComponent
    {
        if (!$this->hasComponent($componentName)) {
            return null;
        }

        return array_get($this->settings['components'], $componentName);
    }

    /**
     * Checks if the object has a component with the specified name.
     */
    public function hasComponent(string $componentName): bool
    {
        $components = $this->settings['components'] ?? [];

        return array_has(is_array($components) ? $components : [], $componentName);
    }

    public function updateComponent(string $alias, array $properties): bool
    {
        $attributes = $this->attributes;

        $newAlias = array_get($properties, 'alias');
        if ($newAlias && $newAlias !== $alias) {
            $attributes = array_replace_key($attributes, $alias, $newAlias);
            $alias = $newAlias;
        }

        unset($properties['alias']);

        $attributes['settings']['components'][$alias] = $properties;
        $this->attributes = $attributes;

        return $this->save();
    }

    public function sortComponents(array $priorities)
    {
        $priorities = array_flip($priorities);
        $components = array_sort(array_get($this->settings, 'components', []),
            function($value, $key) use ($priorities) {
                return $priorities[$key] ?? 0;
            }
        );

        $this->attributes['settings']['components'] = $components;
    }

    public function getComponents(): array
    {
        return $this->settings['components'] ?? [];
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
