<?php

namespace Igniter\Admin\Events;

use Igniter\Flame\Traits\EventDispatchable;
use Illuminate\Support\Collection;

class NavigationExtendUserMenuLinksEvent
{
    use EventDispatchable;

    public function __construct(public Collection $links)
    {
    }

    public static function eventName()
    {
        return 'admin.menu.extendUserMenuLinks';
    }
}
