<?php

namespace Igniter\Admin\Events;

use Igniter\Admin\Models\Order;
use Igniter\Admin\Models\PaymentLog;
use Igniter\Flame\Traits\EventDispatchable;

class OrderRefundProcessedEvent
{
    use EventDispatchable;

    protected Order $order;

    public function __construct(public PaymentLog $paymentLog)
    {
        $this->order = $paymentLog->order;
    }

    public static function eventName()
    {
        return 'admin.order.refundProcessed';
    }
}
