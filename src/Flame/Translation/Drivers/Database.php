<?php

namespace Igniter\Flame\Translation\Drivers;

use Igniter\Flame\Igniter;
use Igniter\Flame\Translation\Contracts\Driver;
use Igniter\Flame\Translation\Models\Translation;

class Database implements Driver
{
    /**
     * @return mixed
     */
    public function load($locale, $group, $namespace = null)
    {
        return Igniter::hasDatabase()
            ? Translation::getCached($locale, $group, $namespace)
            : [];
    }
}
