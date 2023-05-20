<?php

namespace Igniter\Admin\Events;

use Igniter\Admin\Models\PaymentLog;

class OrderBeforeRefundProcessedEvent
{
    use \Igniter\Flame\Traits\EventDispatchable;

    public function __construct(public PaymentLog $paymentLog)
    {
        $this->order = $paymentLog->order;
    }

    public static function eventName()
    {
        return 'admin.order.beforeRefundProcessed';
    }
}
