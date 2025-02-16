<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Facades\Igniter\System\Helpers\SystemHelper;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Tests\System\Fixtures\TestExtension;
use Illuminate\Console\Scheduling\Schedule;
use Mockery;

function createExtension()
{
    return new TestExtension(app());
}

it('returns extension meta from config if set', function() {
    $extension = createExtension();
    $config = ['namespace' => 'TestNamespace', 'code' => 'test.code'];
    $extension->extensionMeta($config);

    expect($extension->extensionMeta())->toBe($config);
});

it('returns extension meta from file if config not set', function() {
    $extension = createExtension();
    $config = SystemHelper::extensionConfigFromFile(dirname(File::fromClass(get_class($extension))));

    expect($extension->extensionMeta())->toBe($config);
});

it('disables extension if disabled property is true', function() {
    $extension = createExtension();
    $extension->disabled = true;

    expect($extension->bootingExtension())->toBeNull()
        ->and(app()->call([$extension, 'boot']))->toBeNull();
});

it('loads resources if directory exists', function() {
    $extension = createExtension();
    File::partialMock()->shouldReceive('isDirectory')->with(Mockery::on(function($path) {
        return str_contains($path, '/resources');
    }))->andReturn(true);

    Igniter::shouldReceive('loadResourcesFrom')->once();
    $extension->bootingExtension();
});

it('loads migrations if directory exists', function() {
    $extension = createExtension();
    File::partialMock()->shouldReceive('isDirectory')->with(Mockery::on(function($path) {
        return str_contains($path, '/database/migrations');
    }))->andReturn(true);

    Igniter::shouldReceive('loadMigrationsFrom')->once();
    $extension->bootingExtension();
});

it('defines registration methods', function() {
    $extension = createExtension();

    expect($extension->registerPaymentGateways())->toBeArray()
        ->and($extension->registerNavigation())->toBeArray()
        ->and($extension->registerSchedule(new Schedule))->toBeNull()
        ->and($extension->registerDashboardWidgets())->toBeArray()
        ->and($extension->registerFormWidgets())->toBeArray()
        ->and($extension->registerValidationRules())->toBeArray()
        ->and($extension->registerSettings())->toBeArray();
});
