<?php

namespace Igniter\Tests\System\Database\Seeds;

use Igniter\Flame\Database\Query\Builder;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Database\Seeds\DatabaseSeeder;
use Igniter\System\Database\Seeds\DemoSchemaSeeder;
use Illuminate\Support\Facades\DB;

it('does not run demo schema seeds when seedDemo is false', function() {
    DatabaseSeeder::$seedDemo = false;
    $seeder = mock(DemoSchemaSeeder::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $seeder->shouldNotReceive('seedCategories');
    $seeder->shouldNotReceive('seedMenuOptions');
    $seeder->shouldNotReceive('seedMenuItems');
    $seeder->run();
});

it('seeds records if table is empty', function() {
    DatabaseSeeder::$seedDemo = true;

    // Categories
    Igniter::shouldReceive('getSeedRecords')->with('categories')->andReturn([['name' => 'Category 1']]);
    DB::shouldReceive('table')->with('categories')->andReturn($categoryBuilder = mock(Builder::class));
    $categoryBuilder->shouldReceive('count')->andReturn(0);
    $categoryBuilder->shouldReceive('insert')->with([['name' => 'Category 1']])->once();
    $categoryBuilder->shouldReceive('update')->once();

    // Menu Options
    Igniter::shouldReceive('getSeedRecords')->with('menu_options')->andReturn([
        [
            'name' => 'Menu Option 1',
            'option_values' => [
                ['name' => 'Option Value 1'],
            ],
        ],
    ]);
    DB::shouldReceive('table')->with('menu_options')->andReturn($menuOptionBuilder = mock(Builder::class));
    $menuOptionBuilder->shouldReceive('count')->andReturn(0);
    $menuOptionBuilder->shouldReceive('insertGetId')->andReturn(123);
    $menuOptionBuilder->shouldReceive('update');

    // Menu Items
    Igniter::shouldReceive('getSeedRecords')->with('menus')->andReturn([
        [
            'name' => 'Menu 1',
            'menu_options' => [
                ['name' => 'Menu Option 1'],
            ],
        ],
    ]);
    DB::shouldReceive('table')->with('menus')->andReturn($menuBuilder = mock(Builder::class));
    DB::shouldReceive('table')->with('menu_item_options')->andReturn($menuItemOptionBuilder = mock(Builder::class));
    DB::shouldReceive('table')->with('menu_option_values')->andReturn($menuOptionValueBuilder = mock(Builder::class));
    DB::shouldReceive('table')->with('menu_item_option_values')->andReturn($menuItemOptionValueBuilder = mock(Builder::class));
    $menuBuilder->shouldReceive('count')->andReturn(0);
    $menuBuilder->shouldReceive('insertGetId')->andReturn(123);
    $menuOptionBuilder->shouldReceive('where->first')->andReturn((object)['option_id' => 123]);
    $menuItemOptionBuilder->shouldReceive('insertGetId')->andReturn(123);
    $menuOptionValueBuilder->shouldReceive('where->get')->andReturn(collect([
        (object)[
            'option_value_id' => 123,
            'price' => 10,
            'priority' => 1,
        ],
    ]));
    $menuOptionValueBuilder->shouldReceive('insert')->once();
    $menuItemOptionValueBuilder->shouldReceive('insertGetId')->once();
    $menuBuilder->shouldReceive('update')->once();
    $menuItemOptionBuilder->shouldReceive('update')->once();
    $menuItemOptionValueBuilder->shouldReceive('update')->once();

    (new DemoSchemaSeeder)->run();
});
