<?php

namespace Igniter\Tests\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Model\AdminLevel;

it('initialize correctly', function() {
    $adminLevel = new AdminLevel(1, 'Country', 'US');

    expect($adminLevel->getLevel())->toBe(1)
        ->and($adminLevel->getName())->toBe('Country')
        ->and($adminLevel->getCode())->toBe('US')
        ->and((string)$adminLevel)->toBe('Country');
});
