<?php

namespace Igniter\Admin\Events\Order;

use Igniter\Admin\Models\Order;
use Igniter\Flame\Traits\EventDispatchable;

class PaymentProcessed
{
    use EventDispatchable;

    protected static $dispatchNamespacedEvent = 'admin.order.paymentProcessed';

    public function __construct(public Order $order)
    {
    }
}
