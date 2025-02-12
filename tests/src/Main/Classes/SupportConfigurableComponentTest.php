<?php

namespace Igniter\Tests\Main\Classes;

use Igniter\Main\Classes\MainController;
use Igniter\Main\Classes\SupportConfigurableComponent;
use Igniter\Main\Traits\ConfigurableComponent;
use Livewire\Component;

beforeEach(function() {
    new class extends MainController
    {
        public function getConfiguredComponent(string $alias): array
        {
            return ['property' => 'value'];
        }
    };
});

it('fills component properties when component uses ConfigurableComponent trait', function() {
    $component = new class extends Component
    {
        use ConfigurableComponent;

        public $properties = [];

        public function getName()
        {
            return 'componentName';
        }

        public function getAlias()
        {
            return null;
        }

        public function fill($values)
        {
            $this->properties = $values;
        }
    };

    $hook = new SupportConfigurableComponent;
    $hook->setComponent($component);
    $hook->mount([], 'key');

    expect($component->properties)->toBe(['property' => 'value']);
});

it('does not fill component properties when component does not use ConfigurableComponent trait', function() {
    $component = new class extends Component
    {
        public $properties = [];

        public function fill($values)
        {
            $this->properties = $values;
        }
    };

    $hook = new SupportConfigurableComponent;
    $hook->setComponent($component);
    $hook->mount([], 'key');

    expect($component->properties)->toBeEmpty();
});

it('fills component properties with alias when component uses ConfigurableComponent trait', function() {
    $component = new class extends Component
    {
        use ConfigurableComponent;

        public $properties = [];

        public function getName()
        {
            return 'componentName';
        }

        public function getAlias()
        {
            return 'alias';
        }

        public function fill($values)
        {
            $this->properties = $values;
        }
    };

    $hook = new SupportConfigurableComponent;
    $hook->setComponent($component);
    $hook->mount([], 'key');

    expect($component->properties)->toBe(['property' => 'value']);
});
