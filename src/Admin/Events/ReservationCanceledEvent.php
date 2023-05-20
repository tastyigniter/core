<?php

namespace Igniter\Admin\Events;

use Igniter\Admin\Models\Reservation;
use Igniter\Flame\Traits\EventDispatchable;

class ReservationCanceledEvent
{
    use EventDispatchable;

    public function __construct(public Reservation $reservation)
    {
    }

    public static function eventName()
    {
        return 'admin.reservation.canceled';
    }
}
