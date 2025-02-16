<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\System\Classes\BaseComponent;
use Igniter\System\Classes\ComponentManager;
use Igniter\Tests\System\Fixtures\TestBladeComponent;
use Igniter\Tests\System\Fixtures\TestComponent;
use Igniter\Tests\System\Fixtures\TestLivewireComponent;
use Illuminate\View\Component;

it('lists component objects', function() {
    $manager = resolve(ComponentManager::class);
    $manager->registerComponents([
        TestComponent::class,
        TestComponent::class => 'test2.component',
        TestBladeComponent::class,
        TestLivewireComponent::class,
    ]);

    expect($manager->listComponentObjects())->toBeGreaterThanOrEqual(4) // Test caching
        ->and($manager->listComponentObjects())->toHaveKeys([
            'testComponent', 'test2.component', 'test::blade-component', 'test::livewire-component',
        ]);
});

it('registers a component with valid definition', function() {
    $manager = resolve(ComponentManager::class);
    $manager->registerComponent(TestComponent::class, ['code' => 'testComponent', 'name' => 'Test Component']);

    $component = $manager->findComponent('testComponent');

    expect($component)->toBeArray()
        ->and($component['code'])->toBe('testComponent')
        ->and($component['name'])->toBe('Test Component');
});

it('throws exception when making unregistered component', function() {
    $manager = resolve(ComponentManager::class);
    $manager->registerComponents([
        'UnregisteredComponent',
    ]);

    expect(fn() => $manager->makeComponent('UnregisteredComponent'))
        ->toThrow(SystemException::class, sprintf('Component "%s" is not registered.', 'UnregisteredComponent'));
});

it('throws an exception when component class does not exists', function() {
    $manager = resolve(ComponentManager::class);
    $manager->registerComponents([
        'NonExistentComponent' => 'component',
    ]);

    expect(fn() => $manager->makeComponent(['component', 'alias']))
        ->toThrow(SystemException::class, sprintf('Component class "%s" not found.', 'NonExistentComponent'));
});

it('throws an exception when component class is invalid', function() {
    $component = new class extends Component
    {
        public static function componentMeta()
        {
            return [
                'code' => 'test3.component',
                'name' => 'Test Component',
            ];
        }

        public function render()
        {
            return '';
        }
    };
    $manager = resolve(ComponentManager::class);
    $manager->registerComponents([
        $component::class => 'component',
    ]);

    expect(fn() => $manager->makeComponent(['component', 'alias']))
        ->toThrow(sprintf('Component class "%s" is not a valid component.', $component::class));
});

it('returns null when resolving unregistered component', function() {
    $manager = resolve(ComponentManager::class);

    expect($manager->resolve('unregistered.component'))->toBeNull();
});

it('returns true if component is registered', function() {
    $manager = resolve(ComponentManager::class);
    $manager->registerComponent(TestComponent::class, ['code' => 'testComponent']);

    expect($manager->hasComponent('testComponent'))->toBeTrue();
});

it('returns false if component is not registered', function() {
    $manager = resolve(ComponentManager::class);

    expect($manager->hasComponent('unregistered.component'))->toBeFalse()
        ->and($manager->findComponent('unregistered.component'))->toBeNull();
});

it('returns component code by class name', function() {
    $manager = resolve(ComponentManager::class);
    $manager->registerComponent(TestComponent::class, ['code' => 'testComponent']);

    expect($manager->findComponentCodeByClass(TestComponent::class))->toBe('testComponent')
        ->and($manager->getCodeAlias('component alias'))->toBe(['component', 'alias'])
        ->and($manager->getCodeAlias('component'))->toBe(['component', 'component']);
});

it('returns null if class name is not registered', function() {
    $manager = resolve(ComponentManager::class);

    expect($manager->findComponentCodeByClass('UnregisteredComponent'))->toBeNull();
});

it('checks if component configurable', function() {
    $manager = resolve(ComponentManager::class);
    $manager->registerComponent(TestBladeComponent::class, ['code' => 'testComponent']);

    expect($manager->isConfigurableComponent('testComponent'))->toBeTrue()
        ->and($manager->isConfigurableComponent('nonexistence.component'))->toBeFalse();
});

it('returns component property configuration', function() {
    $component = mock(BaseComponent::class)->makePartial();
    $component->shouldReceive('defineProperties')->andReturn([
        'property1' => ['type' => 'text', 'label' => 'Property 1'],
        'property2' => ['type' => 'select', 'label' => 'Property 2'],
        'property3' => ['type' => 'radiotoggle', 'label' => 'Property 3'],
    ]);

    $manager = resolve(ComponentManager::class);
    $config = $manager->getComponentPropertyConfig($component);

    expect($config)->toBeArray()
        ->and($config['property1']['type'])->toBe('text')
        ->and($config['property1']['label'])->toBe('Property 1');
});

it('returns component property values', function() {
    $component = mock(BaseComponent::class)->makePartial();
    $component->shouldReceive('defineProperties')->andReturn([
        'property1' => ['type' => 'text'],
    ]);
    $component->shouldReceive('property')->with('property1')->andReturn('value1');

    $manager = resolve(ComponentManager::class);
    $values = $manager->getComponentPropertyValues($component);

    expect($values)->toBeArray()
        ->and($values['property1'])->toBe('value1');
});

it('returns component property rules', function() {
    $component = mock(BaseComponent::class)->makePartial();
    $component->shouldReceive('defineProperties')->andReturn([
        'property1' => ['validationRule' => 'required', 'label' => 'Property 1'],
    ]);

    $manager = resolve(ComponentManager::class);
    [$rules, $attributes] = $manager->getComponentPropertyRules($component);

    expect($rules)->toBeArray()
        ->and($rules['property1'])->toBe('required')
        ->and($attributes['property1'])->toBe('Property 1');
});
