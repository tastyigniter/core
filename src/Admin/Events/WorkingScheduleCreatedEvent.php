<?php

namespace Igniter\Admin\Events;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Location\WorkingSchedule;
use Igniter\Flame\Traits\EventDispatchable;

class WorkingScheduleCreatedEvent
{
    use EventDispatchable;

    public function __construct(public Model $model, public WorkingSchedule $schedule)
    {
    }

    public static function eventName()
    {
        return 'admin.workingSchedule.created';
    }
}
