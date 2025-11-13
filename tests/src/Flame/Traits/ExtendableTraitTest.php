<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Traits;

use BadMethodCallException;
use Igniter\Flame\Support\Extendable;
use Igniter\Flame\Traits\ExtendableTrait;
use Igniter\Tests\Fixtures\Actions\TestControllerAction;
use Igniter\Tests\Fixtures\Classes\TestClass;
use Igniter\Tests\Fixtures\Controllers\ThemeTestController;
use LogicException;

it('extends class with valid extension', function() {
    $class = new class
    {
        use ExtendableTrait;
    };
    TestControllerAction::extensionExtendCallback(fn($extension): bool => $extension instanceof TestControllerAction);
    $class->extendClassWith(TestControllerAction::class);
    $controllerAction = $class->asExtension(TestControllerAction::class);
    $controllerAction->extensionHideField('testProperty');
    $controllerAction->extensionHideMethod('testFunction');

    expect($class->isClassExtendedWith(TestControllerAction::class))->toBeTrue()
        ->and($class->getClassExtension(TestControllerAction::class))->toBeInstanceOf(TestControllerAction::class)
        ->and($class->asExtension('Igniter.Tests.Fixtures.Actions.TestControllerAction'))->toBeInstanceOf(TestControllerAction::class)
        ->and($class->getClassMethods())->toBeArray()->not()->toBeEmpty()
        ->and($controllerAction->extensionIsHiddenField('testProperty'))->toBeTrue()
        ->and($controllerAction->extensionIsHiddenMethod('testFunction'))->toBeTrue();
});

it('throws exception when extending class with invalid extension', function() {
    $class = new class
    {
        use ExtendableTrait;
    };
    expect($class->extendClassWith(''))->toBeNull()
        ->and(fn() => $class->extendClassWith(ThemeTestController::class))
        ->toThrow('Extension '.ThemeTestController::class.' should implement Igniter\Flame\Traits\ExtensionTrait.')
        ->and($class->extendClassWith(TestControllerAction::class))->toBeNull()
        ->and(fn() => $class->extendClassWith(TestControllerAction::class))->toThrow(LogicException::class);
});

it('adds dynamic method and calls it', function() {
    $class = new class
    {
        use ExtendableTrait;
    };
    $class->extendClassWith(TestControllerAction::class);
    $class->addDynamicMethod('dynamicMethod', 'testFunction', TestControllerAction::class);

    expect($class->extendableCall('dynamicMethod'))->toBe('result');
});

it('adds dynamic property and retrieves it', function() {
    $class = new class
    {
        use ExtendableTrait;

        public $property;

        public function __set(string $name, mixed $value): void
        {
            $this->extendableSet($name, $value);
        }
    };
    $class->extendClassWith(TestControllerAction::class);
    $class->addDynamicProperty('dynamicProperty', 'dynamicValue');

    expect($class->extendableSet('viewPath', ['path/to/view']))->toBeNull()
        ->and($class->addDynamicProperty('dynamicProperty', 'dynamicValue'))->toBeNull()
        ->and($class->extendableGet('nonExistentProperty'))->toBeNull()
        ->and($class->extendableGet('testProperty'))->toBe('value')
        ->and($class->extendableGet('viewPath'))->toBe(['path/to/view'])
        ->and($class->propertyExists('dynamicProperty'))->toBeTrue()
        ->and($class->propertyExists('property'))->toBeTrue()
        ->and($class->propertyExists('testProperty'))->toBeTrue()
        ->and($class->propertyExists('nonExistentProperty'))->toBeFalse()
        ->and($class->dynamicProperty)->toBe('dynamicValue');
});

it('calls method from extension', function() {
    $class = new class extends Extendable
    {
        public array $implement = ['?InvalidOptionalClass', TestControllerAction::class];
    };
    $class->attribute = 'value';
    expect($class->extendableCall('testFunction'))->toBe('result')
        ->and($class::extendableCallStatic('testStaticFunction'))->toBe('staticResult');
});

it('throws exception for undefined method call', function() {
    $class = new class
    {
        use ExtendableTrait;
    };
    $anotherClass = new class extends TestClass
    {
        use ExtendableTrait;
    };
    expect(fn() => $class->extendableCall('undefinedMethod', []))->toThrow(BadMethodCallException::class)
        ->and(fn() => $class::extendableCallStatic('undefinedStaticMethod', []))->toThrow(BadMethodCallException::class)
        ->and(fn() => $anotherClass::extendableCallStatic('undefinedStaticMethod', []))->toThrow(BadMethodCallException::class);
});
