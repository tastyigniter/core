<?php

declare(strict_types=1);

namespace Igniter\Flame\Translation\Drivers;

use Override;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Flame\Translation\Contracts\Driver;
use Igniter\Flame\Translation\Models\Translation;

class Database implements Driver
{
    /**
     * @return mixed
     */
    #[Override]
    public function load($locale, $group, $namespace = null)
    {
        return Igniter::hasDatabase()
            ? Translation::getCached($locale, $group, $namespace)
            : [];
    }
}
