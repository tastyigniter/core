<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Components;

use Igniter\Main\Components\BlankComponent;

it('initializes blank component correctly', function() {
    $component = new BlankComponent(null, [], 'An error occurred');

    expect($component->isHidden)->toBeTrue()
        ->and($component->onRender())->toBe('An error occurred');
});
