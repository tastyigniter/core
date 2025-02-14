<?php

declare(strict_types=1);

namespace Igniter\Main\Events;

class ThemeGetActiveEvent
{
    use \Igniter\Flame\Traits\EventDispatchable;

    public static function eventName(): string
    {
        return 'theme.getActiveTheme';
    }
}
