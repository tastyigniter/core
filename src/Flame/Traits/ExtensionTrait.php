<?php

namespace Igniter\Flame\Traits;

/**
 * Extension trait
 *
 * Allows for "Private traits"
 *
 * Adapted from the October ExtendableTrait
 * @link https://github.com/octobercms/library/tree/master/src/Extension/ExtendableTrait.php
 */
trait ExtensionTrait
{
    /**
     * @var array Used to extend the constructor of an extension class. Eg:
     *
     *     BehaviorClass::extend(function($obj) { })
     */
    protected static array $extensionCallbacks = [];

    /**
     * @var string|null The calling class when using a static method.
     */
    public static ?string $extendableStaticCalledClass = null;

    protected array $extensionHidden = [
        'fields' => [],
        'methods' => ['extensionIsHiddenField', 'extensionIsHiddenField'],
    ];

    public function extensionApplyInitCallbacks(): void
    {
        $classes = array_merge([get_class($this)], class_parents($this));
        foreach ($classes as $class) {
            if (isset(self::$extensionCallbacks[$class]) && is_array(self::$extensionCallbacks[$class])) {
                foreach (self::$extensionCallbacks[$class] as $callback) {
                    $callback($this);
                }
            }
        }
    }

    /**
     * Helper method for `::extend()` static method
     */
    public static function extensionExtendCallback(callable $callback): void
    {
        $class = get_called_class();
        if (
            !isset(self::$extensionCallbacks[$class]) ||
            !is_array(self::$extensionCallbacks[$class])
        ) {
            self::$extensionCallbacks[$class] = [];
        }

        self::$extensionCallbacks[$class][] = $callback;
    }

    protected function extensionHideField(string $name): void
    {
        $this->extensionHidden['fields'][] = $name;
    }

    protected function extensionHideMethod(string $name): void
    {
        $this->extensionHidden['methods'][] = $name;
    }

    public function extensionIsHiddenField(string $name): bool
    {
        return in_array($name, $this->extensionHidden['fields']);
    }

    public function extensionIsHiddenMethod(string $name): bool
    {
        return in_array($name, $this->extensionHidden['methods']);
    }

    public static function getCalledExtensionClass(): ?string
    {
        return self::$extendableStaticCalledClass;
    }
}
