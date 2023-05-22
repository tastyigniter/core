<?php

namespace Igniter\Admin\Models\Observers;

use Igniter\Admin\Models\MenuOption;

class MenuOptionObserver
{
    public function saved(MenuOption $menuOption)
    {
        $menuOption->restorePurgedValues();

        if (array_key_exists('values', $attributes = $menuOption->getAttributes())) {
            $menuOption->addOptionValues(array_get($attributes, 'values', []));
        }
    }

    public function deleting(MenuOption $menuOption)
    {
        $menuOption->locations()->detach();
    }
}
