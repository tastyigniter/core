<?php

namespace Igniter\Tests\Main\Traits;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Exception\SystemException;
use Igniter\Main\Traits\ConfigurableComponent;
use Igniter\Tests\System\Fixtures\TestBladeComponent;
use Igniter\Tests\System\Fixtures\TestLivewireComponent;
use Livewire\Component;
use LogicException;

it('throws exception when componentMeta is not defined', function() {
    $component = new class
    {
        use ConfigurableComponent;
    };
    $this->expectException(LogicException::class);
    $component::componentMeta();
});

it('returns alias when set', function() {
    $component = new class
    {
        use ConfigurableComponent;
    };
    $component->setAlias('testAlias');
    expect($component->getAlias())->toBe('testAlias');
});

it('returns true when component is hidden', function() {
    $component = new class
    {
        use ConfigurableComponent;
    };
    $component->isHidden = true;
    expect($component->isHidden())->toBeTrue();
});

it('returns false when component is not hidden', function() {
    $component = new class
    {
        use ConfigurableComponent;
    };
    $component->isHidden = false;
    expect($component->isHidden())->toBeFalse();
});

it('validates properties and merges with default properties', function() {
    $component = new class extends Component
    {
        use ConfigurableComponent;

        public function defineProperties(): array
        {
            return [
                'alias' => [],
                'definedProperty' => [],
            ];
        }
    };
    $properties = $component->validateProperties(['definedProperty' => 'definedValue']);
    expect($properties)->toBe(['alias' => null, 'definedProperty' => 'definedValue']);
});

it('returns property value if set', function() {
    $component = new class
    {
        use ConfigurableComponent;

        public $property = 'value';
    };
    expect($component->property('property'))->toBe('value');
});

it('returns property options', function() {
    $component = new class
    {
        use ConfigurableComponent;
    };

    $form = new class extends Form
    {
        public function __construct() {}
    };
    $formField = new FormField('testField', 'Field');

    expect($component->getPropertyOptions($form, $formField))->toBe([]);
});

it('returns default value if property not set', function() {
    $component = new class
    {
        use ConfigurableComponent;
    };
    expect($component->property('nonExistentProperty', 'defaultValue'))->toBe('defaultValue');
});

it('throws exception if component is not registered', function() {
    $component = new class
    {
        use ConfigurableComponent;
    };
    $this->expectException(SystemException::class);
    $component::resolve([]);
});

it('resolve livewire component correctly', function() {
    $component = new TestLivewireComponent();

    expect($component::resolve([]))->toBeInstanceOf(TestLivewireComponent::class);
});

it('resolve blade component correctly', function() {
    $component = new TestBladeComponent();

    expect($component::resolve([]))->toBeInstanceOf(TestBladeComponent::class);
});
