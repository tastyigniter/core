<?php

namespace Igniter\Admin\Events\WorkingSchedule;

use DateTimeInterface;
use Igniter\Flame\Location\WorkingSchedule;

class TimeslotValid
{
    use \Igniter\Flame\Traits\EventDispatchable;

    protected static $dispatchNamespacedEvent = 'igniter.workingSchedule.timeslotValid';

    public function __construct(public WorkingSchedule $schedule, public DateTimeInterface $timeslot)
    {
    }
}
