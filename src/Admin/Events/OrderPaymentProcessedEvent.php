<?php

namespace Igniter\Admin\Events;

use Igniter\Admin\Models\Order;
use Igniter\Flame\Traits\EventDispatchable;

class OrderPaymentProcessedEvent
{
    use EventDispatchable;

    public function __construct(public Order $order)
    {
    }

    public static function eventName()
    {
        return 'admin.order.paymentProcessed';
    }
}
