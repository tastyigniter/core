<?php

namespace Igniter\Admin\Facades;

use Illuminate\Support\Facades\Facade;

class AdminLocation extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     * @see \Igniter\Admin\Classes\UserGuard
     */
    protected static function getFacadeAccessor()
    {
        return 'admin.location';
    }
}
