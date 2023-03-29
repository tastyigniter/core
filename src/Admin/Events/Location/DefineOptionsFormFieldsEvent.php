<?php

namespace Igniter\Admin\Events\Location;

use Igniter\Flame\Traits\EventDispatchable;

class DefineOptionsFormFieldsEvent
{
    use EventDispatchable;

    protected static $dispatchNamespacedEvent = 'admin.location.defineOptionsFormFields';
}
