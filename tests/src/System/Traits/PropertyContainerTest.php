<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Traits;

use Igniter\System\Traits\PropertyContainer;

it('validates properties with defaults', function() {
    $propertyContainer = new class
    {
        use PropertyContainer;

        public function defineProperties(): array
        {
            return [
                'property1' => ['default' => 'default1'],
                'property2' => ['default' => 'default2'],
            ];
        }
    };

    $result = $propertyContainer->validateProperties(['property1' => 'value1']);
    expect($result)->toBe(['property1' => 'value1', 'property2' => 'default2']);
});

it('sets multiple properties', function() {
    $propertyContainer = new class
    {
        use PropertyContainer;

        public function defineProperties(): array
        {
            return [
                'property1' => ['default' => 'default1'],
                'property2' => ['default' => 'default2'],
            ];
        }
    };

    $propertyContainer->setProperties(['property1' => 'value1']);
    expect($propertyContainer->getProperties())->toBe(['property1' => 'value1', 'property2' => 'default2']);
});

it('merges multiple properties', function() {
    $propertyContainer = new class
    {
        use PropertyContainer;

        public function defineProperties(): array
        {
            return [
                'property1' => ['default' => 'default1'],
                'property2' => ['default' => 'default2'],
            ];
        }
    };

    $propertyContainer->mergeProperties(['property2' => 'value2']);
    expect($propertyContainer->getProperties())->toBe(['property1' => 'default1', 'property2' => 'value2']);
});

it('sets a single property value', function() {
    $propertyContainer = new class
    {
        use PropertyContainer;
    };

    $propertyContainer->setProperty('property1', 'value1');

    expect($propertyContainer->defineProperties())->toBeArray()
        ->and($propertyContainer->getProperties())->toBe(['property1' => 'value1']);
});

it('returns a defined property value or default', function() {
    $propertyContainer = new class
    {
        use PropertyContainer;
    };

    $propertyContainer->setProperty('property1', 'value1');
    $result = $propertyContainer->property('property1', 'default');
    expect($result)->toBe('value1');

    $result = $propertyContainer->property('property2', 'default');
    expect($result)->toBe('default');
});

it('returns empty array for property options', function() {
    $propertyContainer = new class
    {
        use PropertyContainer;
    };

    $result = $propertyContainer::getPropertyOptions('form', 'field');
    expect($result)->toBe([]);
});
