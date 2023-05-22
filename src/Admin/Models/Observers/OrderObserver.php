<?php

namespace Igniter\Admin\Models\Observers;

use Igniter\Admin\Models\Order;

class OrderObserver
{
    public function creating(Order $order)
    {
        $order->generateHash();

        $order->ip_address = request()->getClientIp();
        $order->user_agent = request()->userAgent();
    }
}
