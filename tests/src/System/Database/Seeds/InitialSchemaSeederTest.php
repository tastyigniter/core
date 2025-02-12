<?php

namespace Igniter\Tests\System\Database\Seeds;

use Igniter\Flame\Database\Query\Builder;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Database\Seeds\DatabaseSeeder;
use Igniter\System\Database\Seeds\InitialSchemaSeeder;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

it('disables foreign key constraints on migrations', function() {
    Event::dispatch(MigrationsStarted::class);
    expect(Schema::disableForeignKeyConstraints())->toBeTrue();
});

it('does not run initial schema seeds when seedInitial is false', function() {
    DatabaseSeeder::$seedInitial = false;
    $seeder = mock(InitialSchemaSeeder::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $seeder->shouldNotReceive('seedCountries');

    expect($seeder->run())->toBeNull();
});

it('seeds records if table is empty', function() {
    DatabaseSeeder::$seedInitial = true;

    // Countries
    Igniter::shouldReceive('getSeedRecords')->with('countries')->andReturn([['name' => 'Country 1']]);
    DB::shouldReceive('table')->with('countries')->andReturn($countryBuilder = mock(Builder::class));
    $countryBuilder->shouldReceive('count')->andReturn(0);
    $countryBuilder->shouldReceive('insert')->once();
    $countryBuilder->shouldReceive('update')->once();

    // Currencies
    Igniter::shouldReceive('getSeedRecords')->with('currencies')->andReturn([['name' => 'Currency 1', 'iso_alpha3' => 'USA']]);
    DB::shouldReceive('table')->with('currencies')->andReturn($currencyBuilder = mock(Builder::class));
    $currencyBuilder->shouldReceive('count')->andReturn(0);
    $countryBuilder->shouldReceive('pluck')->with('country_id', 'iso_code_3')->andReturn(collect(['USA' => 1]));
    $currencyBuilder->shouldReceive('insert')->once();

    // Customer Groups
    Igniter::shouldReceive('getSeedRecords')->with('customer_groups')->andReturn([['name' => 'Customer Group 1']]);
    DB::shouldReceive('table')->with('customer_groups')->andReturn($customerGroupBuilder = mock(Builder::class));
    $customerGroupBuilder->shouldReceive('count')->andReturn(0);
    $customerGroupBuilder->shouldReceive('insert')->once();

    // Languages
    Igniter::shouldReceive('getSeedRecords')->with('languages')->andReturn([['name' => 'Language 1']]);
    DB::shouldReceive('table')->with('languages')->andReturn($languageBuilder = mock(Builder::class));
    $languageBuilder->shouldReceive('count')->andReturn(0);
    $languageBuilder->shouldReceive('insert')->once();
    $languageBuilder->shouldReceive('update')->once();

    // Default Location
    Igniter::shouldReceive('getSeedRecords')->with('location')->andReturn([['name' => 'Location 1']]);
    DB::shouldReceive('table')->with('locations')->andReturn($locationBuilder = mock(Builder::class));
    DB::shouldReceive('table')->with('tables')->andReturn($tableBuilder = mock(Builder::class));
    DB::shouldReceive('table')->with('locationables')->andReturn($locationableBuilder = mock(Builder::class));
    $locationBuilder->shouldReceive('count')->andReturn(0);
    $locationBuilder->shouldReceive('insertGetId')->andReturn(123);
    $locationBuilder->shouldReceive('update')->once();
    $tableBuilder->shouldReceive('count')->andReturn(0);
    $tableBuilder->shouldReceive('insertGetId')->times(14);
    $locationableBuilder->shouldReceive('insert')->times(14);
    $tableBuilder->shouldReceive('update');

    // Mealtimes
    Igniter::shouldReceive('getSeedRecords')->with('mealtimes')->andReturn([['name' => 'Mealtimes 1']]);
    DB::shouldReceive('table')->with('mealtimes')->andReturn($mealtimeBuilder = mock(Builder::class));
    $mealtimeBuilder->shouldReceive('count')->andReturn(0);
    $mealtimeBuilder->shouldReceive('insert')->once();
    $mealtimeBuilder->shouldReceive('update')->once();

    // Settings
    Igniter::shouldReceive('getSeedRecords')->with('settings')->andReturn([['name' => 'Setting 1']]);
    DB::shouldReceive('table')->with('settings')->andReturn($settingBuilder = mock(Builder::class));
    $settingBuilder->shouldReceive('count')->andReturn(0);
    $settingBuilder->shouldReceive('insert')->once();

    // User Groups
    Igniter::shouldReceive('getSeedRecords')->with('user_groups')->andReturn([['name' => 'User Group 1']]);
    DB::shouldReceive('table')->with('admin_user_groups')->andReturn($userGroupBuilder = mock(Builder::class));
    $userGroupBuilder->shouldReceive('count')->andReturn(0);
    $userGroupBuilder->shouldReceive('insert')->times(4);
    $userGroupBuilder->shouldReceive('update')->once();

    // User Roles
    Igniter::shouldReceive('getSeedRecords')->with('user_roles')->andReturn([['name' => 'User Role 1']]);
    DB::shouldReceive('table')->with('admin_user_roles')->andReturn($userRoleBuilder = mock(Builder::class));
    $userRoleBuilder->shouldReceive('count')->andReturn(0);
    $userRoleBuilder->shouldReceive('insert')->times(4);
    $userRoleBuilder->shouldReceive('update')->once();

    // Statuses
    Igniter::shouldReceive('getSeedRecords')->with('statuses')->andReturn([['name' => 'Status 1']]);
    DB::shouldReceive('table')->with('statuses')->andReturn($statusBuilder = mock(Builder::class));
    $statusBuilder->shouldReceive('count')->andReturn(0);
    $statusBuilder->shouldReceive('insert')->once();
    $statusBuilder->shouldReceive('update')->once();

    expect((new InitialSchemaSeeder)->run())->toBeNull();
});
