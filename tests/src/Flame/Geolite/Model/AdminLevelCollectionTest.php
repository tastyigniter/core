<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Exception\GeoliteException;
use Igniter\Flame\Geolite\Model\AdminLevel;
use Igniter\Flame\Geolite\Model\AdminLevelCollection;

it('throws exception when level fails validation', function($level1, $level2, $message) {
    $adminLevel1 = new AdminLevel($level1, 'Country');
    $adminLevel2 = new AdminLevel($level2, 'State');
    expect(fn() => new AdminLevelCollection([$adminLevel1, $adminLevel2]))->toThrow(GeoliteException::class, $message);
})->with([
    'less than 1' => [0, -1, 'Administrative level should be an integer in [1,5], 0 given'],
    'greater than max depth' => [6, 8, 'Administrative level should be an integer in [1,5], 6 given'],
    'duplicate level' => [1, 1, 'Administrative level 1 is defined twice'],
]);

it('sorts levels correctly', function() {
    $adminLevel1 = new AdminLevel(2, 'State');
    $adminLevel2 = new AdminLevel(1, 'Country');
    $collection = new AdminLevelCollection([$adminLevel1, $adminLevel2]);
    expect(array_keys($collection->all()))->toBe([1, 2]);
});
