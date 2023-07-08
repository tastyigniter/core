<?php

namespace Igniter\Admin\Facades;

use Illuminate\Support\Facades\Facade;

class AdminHelper extends Facade
{
    public const HANDLER_REDIRECT = 'X_IGNITER_REDIRECT';

    /**
     * Get the registered name of the component.
     *
     * @return string
     * @see \Igniter\Admin\Helpers\AdminHelper
     */
    protected static function getFacadeAccessor()
    {
        return 'admin.helper';
    }
}