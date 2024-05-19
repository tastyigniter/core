<?php

namespace Igniter\Main\Traits;

use Igniter\Main\Components\BlankComponent;
use Igniter\Main\Template\ComponentPartial;
use Igniter\System\Classes\BaseComponent;
use Igniter\System\Classes\ComponentManager;

trait ComponentMaker
{
    public array $components = [];

    /** Object of the active component, used internally. */
    protected ?BaseComponent $componentContext = null;

    protected function initializeComponents()
    {
        $manager = resolve(ComponentManager::class);

        foreach ($this->layout->getComponents() as $componentName => $properties) {
            [$name, $alias] = $manager->getCodeAlias($componentName);

            if ($manager->isConfigurableComponent($name)) {
                $this->layout->loadedConfigurableComponents[$componentName] = $properties;
            } else {
                $this->addComponent($name, $alias, $properties, true, $manager);
            }
        }

        foreach ($this->page->getComponents() as $componentName => $properties) {
            [$name, $alias] = $manager->getCodeAlias($componentName);

            if ($manager->isConfigurableComponent($name)) {
                $this->page->loadedConfigurableComponents[$componentName] = $properties;
            } else {
                $this->addComponent($name, $alias, $properties, false, $manager);
            }
        }

        // Extensibility
        $this->fireSystemEvent('main.layout.initializeComponents', [$this->layoutObj]);
    }

    /**
     * Renders a requested component default partial.
     * @param string $name The component to load.
     * @param array $params Parameter variables to pass to the view.
     * @param bool $throwException Throw an exception if the partial is not found.
     *
     * @return string|false Partial contents or false if not throwing an exception.
     * @throws \Igniter\Flame\Exception\FlashException
     * @internal  This method is used internally.
     */
    public function renderComponent($name, array $params = [], $throwException = true): string|false
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

    /**
     * Adds a component to the layout object
     *
     * @param mixed $name Component class name or short name
     * @param string $alias Alias to give the component
     * @param array $properties Component properties
     * @param bool $addToLayout Add to layout or page
     * @param null|\Igniter\System\Classes\ComponentManager $manager Component manager
     *
     * @return \Igniter\System\Classes\BaseComponent Component object
     * @throws \Exception
     */
    public function addComponent(string $name, string $alias, array $properties = [], bool $addToLayout = false, ?ComponentManager $manager = null): BaseComponent
    {
        $codeObj = $addToLayout ? $this->layoutObj : $this->pageObj;
        $templateObj = $addToLayout ? $this->layout : $this->page;

        $componentObj = $manager->makeComponent([$name, $alias], $codeObj, $properties);
        $componentObj->initialize();

        $this->vars[$alias] = $componentObj;
        $templateObj->loadedComponents[$alias] = $componentObj;

        return $componentObj;
    }

    public function hasComponent(string $alias): bool
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
     */
    public function findComponentByAlias(string $alias): ?BaseComponent
    {
        return $this->page->loadedComponents[$alias] ?? $this->layout->loadedComponents[$alias] ?? null;
    }

    /**
     * Searches the layout components by an AJAX handler
     */
    public function findComponentByHandler(string $handler): ?BaseComponent
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
     */
    public function findComponentByPartial(string $partial): ?BaseComponent
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

    public function getConfiguredComponent(string $alias): array
    {
        return $this->page->loadedConfigurableComponents[$alias] ?? $this->layout->loadedConfigurableComponents[$alias] ?? [];
    }

    public function setComponentContext(?BaseComponent $component)
    {
        $this->componentContext = $component;
    }

    protected function loadComponentPartial(string $name, bool $throwException = true): ComponentPartial|false
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
        if (!str_contains($partialName, '/')) {
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
}
