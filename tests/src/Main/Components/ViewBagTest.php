<?php

namespace Igniter\Tests\Main\Components;

use Igniter\Main\Components\ViewBag;

it('validates properties and returns them unchanged', function() {
    $properties = ['customField' => 'customValue'];

    expect((new ViewBag())->validateProperties($properties))->toBe($properties);
});

it('returns property value when accessed via magic getter', function() {
    $viewBag = new ViewBag(null, ['customField' => 'customValue']);
    expect($viewBag->customField)->toBe('customValue')
        ->and($viewBag->nonExistentField)->toBeNull();
});

it('returns true when property exists via magic isset', function() {
    $viewBag = new ViewBag(null, ['customField' => 'customValue']);
    expect(isset($viewBag->customField))->toBeTrue()
        ->and(isset($viewBag->nonExistentField))->toBeFalse();
});

it('defines properties and returns them with title and type', function() {
    $viewBag = new ViewBag(null, ['customField' => 'customValue']);
    $expected = [
        'customField' => [
            'title' => 'customField',
            'type' => 'text',
        ],
    ];
    expect($viewBag->defineProperties())->toBe($expected);
});
