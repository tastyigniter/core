<?php

namespace Igniter\Main\Events\Theme;

class GetActiveTheme
{
    use \Igniter\Flame\Traits\EventDispatchable;

    protected static $dispatchLegacyEvent = 'theme.getActiveTheme';
}
