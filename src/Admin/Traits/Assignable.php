<?php

namespace Igniter\Admin\Traits;

use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Models\AssignableLog;
use Igniter\Admin\Models\UserGroup;
use Illuminate\Database\Eloquent\Builder;

trait Assignable
{
    public static function bootAssignable()
    {
        static::extend(function (self $model) {
            $model->relation['belongsTo']['assignee'] = [\Igniter\Admin\Models\User::class];
            $model->relation['belongsTo']['assignee_group'] = [\Igniter\Admin\Models\UserGroup::class];
            $model->relation['morphMany']['assignable_logs'] = [
                \Igniter\Admin\Models\AssignableLog::class, 'name' => 'assignable', 'delete' => true,
            ];

            $model->addCasts([
                'assignee_id' => 'integer',
                'assignee_group_id' => 'integer',
                'assignee_updated_at' => 'dateTime',
            ]);
        });

        self::saved(function (self $model) {
            $model->performOnAssignableAssigned();
        });
    }

    protected function performOnAssignableAssigned()
    {
        if (
            $this->wasChanged('status_id')
            && strlen($this->assignee_group_id)
        ) AssignableLog::createLog($this);
    }

    //
    //
    //

    /**
     * @param \Igniter\Admin\Models\User $assignee
     * @return bool
     */
    public function assignTo($assignee)
    {
        if (is_null($this->assignee_group))
            return false;

        return $this->updateAssignTo($this->assignee_group, $assignee);
    }

    /**
     * @param \Igniter\Admin\Models\UserGroup $group
     * @return bool
     */
    public function assignToGroup($group)
    {
        return $this->updateAssignTo($group);
    }

    public function updateAssignTo($group = null, $assignee = null)
    {
        if (is_null($group))
            $group = $this->assignee_group;

        $this->assignee_group()->associate($group);

        $oldAssignee = $this->assignee;
        if (!is_null($assignee))
            $this->assignee()->associate($assignee);

        $this->fireSystemEvent('admin.assignable.beforeAssignTo', [$group, $assignee, $oldAssignee]);

        $this->save();

        if (!$log = AssignableLog::createLog($this))
            return false;

        $this->fireSystemEvent('admin.assignable.assigned', [$log]);

        return $log;
    }

    public function isAssignedToStaffGroup($staff)
    {
        return $staff
            ->groups()
            ->whereKey($this->assignee_group_id)
            ->exists();
    }

    public function hasAssignTo()
    {
        return !is_null($this->assignee);
    }

    public function hasAssignToGroup()
    {
        return !is_null($this->assignee_group);
    }

    public function listGroupAssignees()
    {
        if (!$this->assignee_group instanceof UserGroup)
            return [];

        return $this->assignee_group->listAssignees();
    }

    //
    // Scopes
    //

    /**
     * @param \Igniter\Flame\Database\Query\Builder $query
     * @param null $assignedTo
     * @return mixed
     */
    public function scopeFilterAssignedTo($query, $assignedTo = null)
    {
        if ($assignedTo == 1)
            return $query->whereNull('assignee_id');

        $staffId = optional(AdminAuth::staff())->getKey();
        if ($assignedTo == 2)
            return $query->where('assignee_id', $staffId);

        return $query->where('assignee_id', '!=', $staffId);
    }

    /**
     * @param \Igniter\Flame\Database\Query\Builder $query
     * @return mixed
     */
    public function scopeWhereUnAssigned($query)
    {
        return $query->whereNotNull('assignee_group_id')->whereNull('assignee_id');
    }

    /**
     * @param \Igniter\Flame\Database\Query\Builder $query
     * @param $assigneeId
     * @return mixed
     */
    public function scopeWhereAssignTo($query, $assigneeId)
    {
        return $query->where('assignee_id', $assigneeId);
    }

    /**
     * @param \Igniter\Flame\Database\Query\Builder $query
     * @param $assigneeGroupId
     * @return mixed
     */
    public function scopeWhereAssignToGroup($query, $assigneeGroupId)
    {
        return $query->where('assignee_group_id', $assigneeGroupId);
    }

    /**
     * @param \Igniter\Flame\Database\Query\Builder $query
     * @param array $assigneeGroupIds
     * @return mixed
     */
    public function scopeWhereInAssignToGroup($query, array $assigneeGroupIds)
    {
        return $query->whereIn('assignee_group_id', $assigneeGroupIds);
    }

    /**
     * @param \Igniter\Flame\Database\Query\Builder $query
     * @return mixed
     */
    public function scopeWhereHasAutoAssignGroup($query)
    {
        return $query->whereHas('assignee_group', function (Builder $query) {
            $query->where('auto_assign', 1);
        });
    }
}
