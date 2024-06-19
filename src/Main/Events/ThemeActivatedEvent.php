<?php

namespace Igniter\Main\Events;

use Igniter\Flame\Traits\EventDispatchable;
use Igniter\Main\Models\Theme;

class ThemeActivatedEvent
{
    use EventDispatchable;

    public function __construct(public Theme $theme) {}

    public static function eventName(): string
    {
        return 'main.theme.activated';
    }
}
