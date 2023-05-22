<?php

namespace Igniter\Admin\Models\Scopes;

use Igniter\Flame\Database\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CategoryScope extends Scope
{
    public function addWhereHasMenus()
    {
        return function (Builder $builder) {
            return $builder->whereExists(function ($q) {
                $prefix = DB::getTablePrefix();
                $q->select(DB::raw(1))
                    ->from('menu_categories')
                    ->join('menus', 'menus.menu_id', '=', 'menu_categories.menu_id')
                    ->whereIsEnabled()
                    ->whereRaw($prefix.'categories.category_id = '.$prefix.'menu_categories.category_id');
            });
        };
    }
}
