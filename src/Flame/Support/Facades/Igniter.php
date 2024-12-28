<?php

namespace Igniter\Flame\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Igniter extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @see \Igniter\Flame\Filesystem\Filesystem
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Igniter\Flame\Support\Igniter::class;
    }
}
