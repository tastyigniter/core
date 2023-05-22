<?php

namespace Igniter\Admin\Models\Observers;

use Igniter\Admin\Models\Reservation;

class ReservationObserver
{
    public function creating(Reservation $reservation)
    {
        $reservation->generateHash();

        $reservation->ip_address = request()->getClientIp();
        $reservation->user_agent = request()->userAgent();
    }

    public function saved(Reservation $reservation)
    {
        $reservation->restorePurgedValues();

        if (array_key_exists('tables', $attributes = $reservation->getAttributes())) {
            $reservation->addReservationTables((array)array_get($attributes, 'tables', []));
        }

        if ($reservation->location->getOption('auto_allocate_table', 1) && !$reservation->tables()->count()) {
            $reservation->addReservationTables($reservation->getNextBookableTable()->pluck('table_id')->all());
        }
    }
}
