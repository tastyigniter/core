<?php

namespace Igniter\Admin\Events\WorkingSchedule;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Location\WorkingSchedule;
use Igniter\Flame\Traits\EventDispatchable;

class Created
{
    use EventDispatchable;

    protected static $dispatchNamespacedEvent = 'admin.workingSchedule.Created';

    public function __construct(public Model $model, public WorkingSchedule $schedule)
    {
    }
}
