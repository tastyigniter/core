<?php

namespace Igniter\Admin\Events\Reservation;

use Igniter\Admin\Models\Reservation;
use Igniter\Flame\Traits\EventDispatchable;

class CancelEvent
{
    use EventDispatchable;

    protected static $dispatchNamespacedEvent = 'admin.reservation.canceled';

    public function __construct(public Reservation $reservation)
    {
    }
}
