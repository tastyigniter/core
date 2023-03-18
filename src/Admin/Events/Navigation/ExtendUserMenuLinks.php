<?php

namespace Igniter\Admin\Events\Navigation;

use Igniter\Flame\Traits\EventDispatchable;
use Illuminate\Support\Collection;

class ExtendUserMenuLinks
{
    use EventDispatchable;

    protected static $dispatchNamespacedEvent = 'admin.menu.extendUserMenuLinks';

    public function __construct(public Collection $links)
    {
    }
}
