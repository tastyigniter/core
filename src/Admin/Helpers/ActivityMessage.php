<?php

namespace Igniter\Admin\Helpers;

use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Models\User;
use Igniter\Flame\ActivityLog\Models\Activity;

class ActivityMessage
{
    public static function attachCauserPlaceholders($line, Activity $activity)
    {
        $prefix = '<b>:causer.staff_name</b> ';
        $self = lang('igniter::system.activities.activity_self');

        if (!$activity->causer instanceof User)
            $prefix = '<b>'.lang('igniter::system.activities.activity_system').'</b> ';

        if ($activity->causer && $activity->causer->user_id == AdminAuth::getId())
            $prefix = '<b>'.ucfirst($self).'</b> ';

        return $prefix.lang($line);
    }

    public static function attachAssignedPlaceholders($line, Activity $activity)
    {
        $self = lang('igniter::system.activities.activity_self');

        $prefix = '<b>:causer.staff_name</b> ';
        if (!$activity->causer instanceof User)
            $prefix = '<b>'.lang('igniter::system.activities.activity_system').'</b> ';

        if ($activity->causer && $activity->causer->user_id == AdminAuth::getId())
            $prefix = '<b>'.ucfirst($self).'</b> ';

        $assigneeId = $activity->properties->get('assignee_id');
        if (!$assigneeId && strlen($activity->properties->get('assignee_group_id'))) {
            $suffix = ' <b>:properties.assignee_group_name</b>';
        }
        elseif ($assigneeId == optional(AdminAuth::staff())->getKey()) {
            $suffix = ' <b>'.$self.'</b>';
        }
        else {
            $suffix = ' <b>:properties.assignee_name</b>';
        }

        return $prefix.lang($line).$suffix;
    }
}
