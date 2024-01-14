<?php

namespace Igniter\System\Database\Seeds;

use Igniter\Flame\Igniter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSchemaSeeder extends Seeder
{
    /**
     * Run the demo schema seeds.
     */
    public function run()
    {
        if (!DatabaseSeeder::$seedDemo) {
            return;
        }

        $this->seedCategories();

        $this->seedMenuOptions();

        $this->seedMenuItems();
    }

    protected function seedCategories()
    {
        if (DB::table('categories')->count()) {
            return;
        }

        DB::table('categories')->insert(Igniter::getSeedRecords('categories'));

        DB::table('categories')->update(['updated_at' => now(), 'created_at' => now()]);
    }

    protected function seedMenuOptions()
    {
        if (DB::table('menu_options')->count()) {
            return;
        }

        foreach (Igniter::getSeedRecords('menu_options') as $menuOption) {
            $optionId = DB::table('menu_options')->insertGetId(array_except($menuOption, 'option_values'));

            foreach (array_get($menuOption, 'option_values') as $optionValue) {
                DB::table('menu_option_values')->insert(array_merge($optionValue, [
                    'option_id' => $optionId,
                ]));
            }
        }

        DB::table('menu_options')->update(['updated_at' => now(), 'created_at' => now()]);
    }

    protected function seedMenuItems()
    {
        if (DB::table('menus')->count()) {
            return;
        }

        foreach (Igniter::getSeedRecords('menus') as $menu) {
            $menuId = DB::table('menus')->insertGetId(array_except($menu, 'menu_options'));

            foreach (array_get($menu, 'menu_options', []) as $name) {
                $option = DB::table('menu_options')->where('option_name', $name)->first();

                $menuOptionId = DB::table('menu_item_options')->insertGetId([
                    'option_id' => $option->option_id,
                    'menu_id' => $menuId,
                ]);

                $optionValues = DB::table('menu_option_values')->where('option_id', $option->option_id)->get();

                foreach ($optionValues as $optionValue) {
                    DB::table('menu_item_option_values')->insertGetId([
                        'menu_option_id' => $menuOptionId,
                        'option_value_id' => $optionValue->option_value_id,
                        'override_price' => $optionValue->price,
                        'priority' => $optionValue->priority,
                    ]);
                }
            }
        }

        DB::table('menus')->update(['updated_at' => now(), 'created_at' => now()]);
        DB::table('menu_item_options')->update(['updated_at' => now(), 'created_at' => now()]);
        DB::table('menu_item_option_values')->update(['updated_at' => now(), 'created_at' => now()]);
    }
}
