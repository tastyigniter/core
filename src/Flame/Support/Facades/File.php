<?php

namespace Igniter\Flame\Support\Facades;

use Igniter\Flame\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade as IlluminateFacade;

class File extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @see \Igniter\Flame\Filesystem\Filesystem
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Filesystem::class;
    }
}
