<?php

namespace Igniter\Admin\Events\Location;

use Igniter\Flame\Traits\EventDispatchable;

class DefineOptionsFormFields
{
    use EventDispatchable;

    protected static $dispatchNamespacedEvent = 'admin.location.defineOptionsFormFields';
}
