<?php

namespace Igniter\Admin\Events;

use DateTimeInterface;
use Igniter\Flame\Location\WorkingSchedule;

class WorkingScheduleTimeslotValidEvent
{
    use \Igniter\Flame\Traits\EventDispatchable;

    public function __construct(public WorkingSchedule $schedule, public DateTimeInterface $timeslot)
    {
    }

    public static function eventName()
    {
        return 'admin.workingSchedule.timeslotValid';
    }
}
