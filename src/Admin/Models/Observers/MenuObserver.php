<?php

namespace Igniter\Admin\Models\Observers;

use Igniter\Admin\Models\Menu;

class MenuObserver
{
    public function saved(Menu $menu)
    {
        $menu->restorePurgedValues();

        if (array_key_exists('menu_options', $attributes = $menu->getAttributes())) {
            $menu->addMenuOption((array)array_get($attributes, 'menu_options', []));
        }

        if (array_key_exists('special', $attributes)) {
            $menu->addMenuSpecial((array)array_get($attributes, 'special', []));
        }
    }

    public function deleting(Menu $menu)
    {
        $menu->categories()->detach();
        $menu->mealtimes()->detach();
        $menu->ingredients()->detach();
        $menu->locations()->detach();
    }
}
