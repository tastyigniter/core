<?php

namespace Igniter\Admin\Events\Order;

use Igniter\Admin\Models\PaymentLog;

class BeforeRefundProcessed
{
    use \Igniter\Flame\Traits\EventDispatchable;

    protected static $dispatchNamespacedEvent = 'admin.order.beforeRefundProcessed';

    public function __construct(public PaymentLog $paymentLog)
    {
        $this->order = $paymentLog->order;
    }
}
