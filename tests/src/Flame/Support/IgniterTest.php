<?php

namespace Igniter\Tests\Flame\Support;

use Igniter\Flame\Support\Igniter;
use Igniter\Tests\Fixtures\Models\TestModel;
use Igniter\User\Models\Customer;
use Igniter\User\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

it('returns themes path when set', function() {
    config(['igniter-system.themesPath' => '/default/themes/path']);
    expect(resolve(Igniter::class)->themesPath())->toBe('/default/themes/path');

    $igniter = resolve(Igniter::class);
    $igniter->useThemesPath('/custom/themes/path');
    expect($igniter->themesPath())->toBe('/custom/themes/path');
});

it('returns extensions path when set', function() {
    config(['igniter-system.extensionsPath' => '/default/extensions/path']);
    expect(resolve(Igniter::class)->extensionsPath())->toBe('/default/extensions/path');

    $igniter = resolve(Igniter::class);
    $igniter->useExtensionsPath('/custom/extensions/path');
    expect($igniter->extensionsPath())->toBe('/custom/extensions/path');
});

it('returns temp path when set', function() {
    config(['igniter-system.tempPath' => '/default/temp/path']);
    expect(resolve(Igniter::class)->tempPath())->toBe('/default/temp/path');

    $igniter = resolve(Igniter::class);
    $igniter->useTempPath('/custom/temp/path');
    expect($igniter->tempPath())->toBe('/custom/temp/path');
});

it('checks if running in admin area', function() {
    expect(resolve(Igniter::class)->runningInAdmin())->toBeFalse();

    $request = mock(Request::class);
    $request->shouldReceive('setUserResolver')->andReturnNull();
    $request->shouldReceive('path')->andReturn('admin/dashboard');
    app()->instance('request', $request);

    expect(resolve(Igniter::class)->runningInAdmin())->toBeTrue();
});

it('checks if database connection is present', function() {
    $connection = mock(Connection::class);
    $connection->shouldReceive('getSchemaBuilder')->andThrow(new \Exception());
    DB::shouldReceive('connection')->andReturn($connection);

    expect(resolve(Igniter::class)->hasDatabase())->toBeFalse();
});

it('ignores migrations by namespace', function() {
    $igniter = resolve(Igniter::class);
    $igniter->loadMigrationsFrom('/path/to/migrations', 'test.namespace');
    $igniter->ignoreMigrations('test.namespace');
    expect($igniter->migrationPath())->not()->toHaveKey('test.namespace');
});

it('returns records to seed', function() {
    $igniter = resolve(Igniter::class);
    expect($igniter->getSeedRecords('countries'))->not()->toBeEmpty();
});

it('loads views from extensions', function() {
    $igniter = resolve(Igniter::class);
    $igniter->loadViewsFrom('/path/to/extensions', 'test.namespace');
    expect(view()->getFinder()->getHints())->toHaveKey('test.namespace');
});

it('checks if user is admin or customer', function() {
    $user = mock(User::class);
    $customer = mock(Customer::class);
    expect(resolve(Igniter::class)->isUser($user))->toBeTrue()
        ->and(resolve(Igniter::class)->isUser($customer))->toBeTrue()
        ->and(resolve(Igniter::class)->isAdminUser($user))->toBeTrue()
        ->and(resolve(Igniter::class)->isAdminUser($customer))->toBeFalse()
        ->and(resolve(Igniter::class)->isCustomer($customer))->toBeTrue()
        ->and(resolve(Igniter::class)->isCustomer($user))->toBeFalse();
});

it('registers prunable models', function() {
    $igniter = resolve(Igniter::class);
    $igniter->prunableModel(TestModel::class);
    expect($igniter->prunableModels())->toContain(TestModel::class);
});

it('autoload extensions by default', function() {
    $igniter = resolve(Igniter::class);
    expect($igniter->autoloadExtensions())->toBeTrue();

    $igniter->autoloadExtensions(false);
    expect($igniter->autoloadExtensions())->toBeFalse();
});

it('disables theme routes registration', function() {
    $igniter = resolve(Igniter::class);
    expect($igniter->disableThemeRoutes())->toBeFalse();

    $igniter->disableThemeRoutes(true);
    expect($igniter->disableThemeRoutes())->toBeTrue();
});

it('registers publishable theme files', function() {
    $igniter = resolve(Igniter::class);
    $igniter->publishesThemeFiles([
        '/path/to/source' => '/path/to/destination',
        '/path/to/source-and-destination',
        'path/to/source-and-null-destination' => null,
    ]);
    expect($igniter->publishableThemeFiles())
        ->toHaveKey('/path/to/source', '/path/to/destination')
        ->toHaveKey('/path/to/source-and-destination', '/path/to/source-and-destination')
        ->toHaveKey('path/to/source-and-null-destination', 'path/to/source-and-null-destination');
});
