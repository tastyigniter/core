<?php

namespace Igniter\Admin\Events\Order;

use Igniter\Admin\Models\Order;
use Igniter\Flame\Traits\EventDispatchable;

class BeforePaymentProcessed
{
    use EventDispatchable;

    protected static $dispatchNamespacedEvent = 'admin.order.beforePaymentProcessed';

    public function __construct(public Order $order)
    {
    }
}
