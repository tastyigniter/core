<?php

namespace Igniter\Main\Events\Theme;

use Igniter\Flame\Traits\EventDispatchable;
use Igniter\Main\Models\Theme;

class Activated
{
    use EventDispatchable;

    protected static $dispatchNamespacedEvent = 'main.theme.activated';

    public function __construct(public Theme $theme)
    {
    }
}
