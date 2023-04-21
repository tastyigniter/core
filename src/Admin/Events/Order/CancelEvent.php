<?php

namespace Igniter\Admin\Events\Order;

use Igniter\Admin\Models\Order;
use Igniter\Flame\Traits\EventDispatchable;

class CancelEvent
{
    use EventDispatchable;

    protected static $dispatchNamespacedEvent = 'admin.order.canceled';

    public function __construct(public Order $order)
    {
    }
}
