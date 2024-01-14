<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Pagic\TemplateCode;

/**
 * Components class for TastyIgniter.
 * Provides utility functions for working with components.
 */
class ComponentManager
{
    /** Cache of registration callbacks. */
    public array $registry = [];

    /** Cache of registration components callbacks. */
    protected array $componentsCallbacks = [];

    /** An array where keys are codes and values are class paths. */
    protected array $codeMap = [];

    /** An array where keys are class paths and values are codes. */
    protected array $classMap = [];

    /** An array containing references to a corresponding extension for each component class. */
    protected array $extensionMap = [];

    /** A cached array of components component_meta. */
    protected ?array $components = null;

    /**
     * Scans each extension and loads it components.
     */
    protected function loadComponents()
    {
        // Load manually registered components
        foreach ($this->componentsCallbacks as $callback) {
            $callback($this);
        }

        // Load extensions components
        $extensions = resolve(ExtensionManager::class)->getExtensions();
        foreach ($extensions as $extension) {
            $components = $extension->registerComponents();
            if (!is_array($components)) {
                continue;
            }

            foreach ($components as $class_path => $component) {
                $this->registerComponent($class_path, $component, $extension);
            }
        }
    }

    /**
     * Manually registers a component.
     * Usage:
     * <pre>
     *   resolve(ComponentManager::class)->registerComponents(function($manager){
     *       $manager->registerComponent('account_module/components/Account_module', array(
     *          'name' => 'account_module',
     *            'title' => 'Account Component',
     *            'description' => '..',
     *        );
     *   });
     * </pre>
     */
    public function registerComponents(callable $definitions)
    {
        $this->componentsCallbacks[] = $definitions;
    }

    /**
     * Registers a single component.
     */
    public function registerComponent(string $classPath, null|string|array $component = null, ?BaseExtension $extension = null)
    {
        if (!$this->classMap) {
            $this->classMap = [];
        }

        if (!$this->codeMap) {
            $this->codeMap = [];
        }

        if (is_string($component)) {
            $component = ['code' => $component];
        }

        $component = array_merge([
            'code' => null,
            'name' => 'Component',
            'description' => null,
        ], $component);

        $code = $component['code'] ?? strtolower(basename($classPath));

        $this->codeMap[$code] = $classPath;
        $this->classMap[$classPath] = $code;
        $this->components[$code] = array_merge($component, [
            'code' => $code,
            'path' => $classPath,
        ]);

        if ($extension !== null) {
            $this->extensionMap[$classPath] = $extension;
        }
    }

    /**
     * Returns a list of registered components.
     */
    public function listComponents(): ?array
    {
        if ($this->components == null) {
            $this->loadComponents();
        }

        return $this->components;
    }

    /**
     * Returns a class name from a component code
     * Normalizes a class name or converts an code to it's class name.
     */
    public function resolve(string $name): ?string
    {
        $this->listComponents();

        if (isset($this->codeMap[$name])) {
            return $this->codeMap[$name];
        }

        $name = $this->convertCodeToPath($name);
        if (isset($this->classMap[$name])) {
            return $name;
        }

        return null;
    }

    /**
     * Checks to see if a component has been registered.
     */
    public function hasComponent(string $name): bool
    {
        $class_path = $this->resolve($name);
        if (!$class_path) {
            return false;
        }

        return isset($this->classMap[$class_path]);
    }

    /**
     * Returns component details based on its name.
     */
    public function findComponent($name): ?BaseComponent
    {
        if (!$this->hasComponent($name)) {
            return null;
        }

        return $this->components[$name];
    }

    /**
     * Makes a component/gateway object with properties set.
     */
    public function makeComponent(string $name, ?TemplateCode $page = null, array $params = []): BaseComponent
    {
        $className = $this->resolve($name);
        if (!$className) {
            throw new SystemException(sprintf('Component "%s" is not registered.', $name));
        }

        if (!class_exists($className)) {
            throw new SystemException(sprintf('Component class "%s" not found.', $className));
        }

        // Create and register the new controller.
        $component = new $className($page, $params);
        $component->name = $name;

        return $component;
    }

    /**
     * Returns a parent extension for a specific component.
     */
    public function findComponentExtension(string $component): ?BaseExtension
    {
        $classPath = $this->resolve($component);

        return $this->extensionMap[$classPath] ?? null;
    }

    /**
     * Convert class alias to class path
     */
    public function convertCodeToPath(string $alias): string
    {
        if (!str_contains($alias, '/')) {
            return $alias;
        }

        return $alias.'/components/'.ucfirst($alias);
    }

    //
    // Helpers
    //

    /**
     * Returns a component property configuration as a JSON string or array.
     */
    public function getComponentPropertyConfig(BaseComponent $component, bool $addAliasProperty = true): array
    {
        $result = [];

        if ($addAliasProperty) {
            $property = [
                'property' => 'alias',
                'label' => '',
                'type' => 'text',
                'comment' => '',
                'validationRule' => ['required', 'regex:^[a-zA-Z]+$'],
                'validationMessage' => '',
                'required' => true,
                'showExternalParam' => false,
            ];
            $result['alias'] = $property;
        }

        $properties = $component->defineProperties();
        foreach ($properties as $name => $params) {
            $propertyType = array_get($params, 'type', 'text');

            if (!$this->checkComponentPropertyType($propertyType)) {
                continue;
            }

            $property = [
                'property' => $name,
                'label' => array_get($params, 'label', $name),
                'type' => $propertyType,
                'showExternalParam' => array_get($params, 'showExternalParam', false),
            ];

            if (!in_array($propertyType, ['text', 'number']) && !array_key_exists('options', $params)) {
                $methodName = 'get'.studly_case($name).'Options';
                $property['options'] = [get_class($component), $methodName];
            }

            foreach ($params as $paramName => $paramValue) {
                if (isset($property[$paramName])) {
                    continue;
                }

                $property[$paramName] = $paramValue;
            }

            // Translate human values
            $translate = ['label', 'description', 'options', 'group', 'validationMessage'];
            foreach ($property as $propertyName => $propertyValue) {
                if (!in_array($propertyName, $translate)) {
                    continue;
                }

                if (is_array($propertyValue)) {
                    array_walk($property[$propertyName], function (&$_propertyValue) {
                        $_propertyValue = lang($_propertyValue);
                    });
                } else {
                    $property[$propertyName] = lang($propertyValue);
                }
            }

            $result[$name] = $property;
        }

        return $result;
    }

    /**
     * Returns a component property values.
     */
    public function getComponentPropertyValues(BaseComponent $component): array
    {
        $result = [];

        $result['alias'] = $component->alias;

        $properties = $component->defineProperties();
        foreach ($properties as $name => $params) {
            $result[$name] = $component->property($name);
        }

        return $result;
    }

    public function getComponentPropertyRules(BaseComponent $component): array
    {
        $properties = $component->defineProperties();

        $rules = $attributes = [];
        foreach ($properties as $name => $params) {
            if (strlen($rule = array_get($params, 'validationRule', ''))) {
                $rules[$name] = $rule;
                $attributes[$name] = array_get($params, 'label', $name);
            }
        }

        return [$rules, $attributes];
    }

    protected function checkComponentPropertyType(string $type): bool
    {
        return in_array($type, [
            'text',
            'number',
            'checkbox',
            'radio',
            'select',
            'selectlist',
            'switch',
        ]);
    }
}
