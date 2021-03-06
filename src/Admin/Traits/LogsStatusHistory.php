<?php

namespace Igniter\Admin\Traits;

use Igniter\Admin\Models\Reservation;
use Igniter\Admin\Models\StatusHistory;

trait LogsStatusHistory
{
    public $formWidgetStatusData = [];

    public static function bootLogsStatusHistory()
    {
        self::extend(function (self $model) {
            $model->relation['belongsTo']['status'] = [\Igniter\Admin\Models\Status::class];
            $model->relation['morphMany']['status_history'] = [
                \Igniter\Admin\Models\StatusHistory::class, 'name' => 'object', 'delete' => TRUE,
            ];

            $model->appends[] = 'status_name';

            $model->addCasts([
                'status_id' => 'integer',
                'status_updated_at' => 'dateTime',
            ]);
        });
    }

    public function getStatusNameAttribute()
    {
        return $this->status ? $this->status->status_name : null;
    }

    public function getStatusColorAttribute()
    {
        return $this->status ? $this->status->status_color : null;
    }

    public function getLatestStatusHistory()
    {
        return $this->status_history->first();
    }

    public function addStatusHistory($status, array $statusData = [])
    {
        if (!$this->exists || !$status)
            return false;

        $this->status()->associate($status);

        if (!$history = StatusHistory::createHistory($status, $this, $statusData))
            return FALSE;

        $this->save();

        $this->reloadRelations();

        if ($history->notify) {
            $mailView = ($this instanceof Reservation)
                ? 'igniter.admin::_mail.reservation_update' : 'igniter.admin::_mail.order_update';

            $this->mailSend($mailView, 'customer');
        }

        $this->fireSystemEvent('admin.statusHistory.added', [$history]);

        return $history;
    }

    public function hasStatus($statusId = null)
    {
        if (is_null($statusId))
            return $this->status_history->isNotEmpty();

        return $this->status_history()->where('status_id', $statusId)->exists();
    }

    public function scopeWhereStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    public function scopeWhereHasStatusInHistory($query, $statusId)
    {
        return $query->whereHas('status_history', function ($q) use ($statusId) {
            return $q->where('status_id', $statusId);
        });
    }
}
