<?php

namespace Igniter\Admin\Models;

use Carbon\Carbon;
use Igniter\Cart\Models\Order;
use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;

/**
 * Status History Model Class
 */
class StatusHistory extends Model
{
    use HasFactory;

    /**
     * @var string The database table name
     */
    protected $table = 'status_history';

    protected $primaryKey = 'status_history_id';

    protected $guarded = [];

    protected $appends = ['staff_name', 'status_name', 'notified', 'date_added_since'];

    public $timestamps = true;

    protected $casts = [
        'object_id' => 'integer',
        'user_id' => 'integer',
        'status_id' => 'integer',
        'notify' => 'boolean',
    ];

    public $relation = [
        'belongsTo' => [
            'user' => \Igniter\User\Models\User::class,
            'status' => [\Igniter\Admin\Models\Status::class, 'status_id'],
        ],
        'morphTo' => [
            'object' => [],
        ],
    ];

    public static function alreadyExists($model, $statusId)
    {
        return self::where('object_id', $model->getKey())
            ->where('object_type', $model->getMorphClass())
            ->where('status_id', $statusId)->exists();
    }

    public function getStaffNameAttribute($value)
    {
        return $this->user->staff_name ?? $value;
    }

    public function getDateAddedSinceAttribute($value)
    {
        return $this->created_at ? time_elapsed($this->created_at) : null;
    }

    public function getStatusNameAttribute($value)
    {
        return ($this->status && $this->status->exists) ? $this->status->status_name : $value;
    }

    public function getNotifiedAttribute()
    {
        return $this->notify == 1 ? lang('igniter::admin.text_yes') : lang('igniter::admin.text_no');
    }

    /**
     * @param \Igniter\Flame\Database\Model|mixed $status
     * @param \Igniter\Flame\Database\Model|mixed $object
     * @param array $options
     * @return static|bool
     */
    public static function createHistory($status, $object, $options = [])
    {
        if (!$status instanceof Status) {
            $status = Status::find($status);
        }

        $statusId = $status->getKey();
        $previousStatus = $object->getOriginal('status_id');

        $model = new static;
        $model->status_id = $statusId;
        $model->object_id = $object->getKey();
        $model->object_type = $object->getMorphClass();
        $model->user_id = array_get($options, 'staff_id', array_get($options, 'user_id'));
        $model->comment = array_get($options, 'comment', $status->status_comment);
        $model->notify = array_get($options, 'notify', $status->notify_customer);

        if ($model->fireSystemEvent('admin.statusHistory.beforeAddStatus', [$object, $statusId, $previousStatus], true) === false) {
            return false;
        }

        $model->save();

        // Update using query to prevent model events from firing
        $object->newQuery()->where($object->getKeyName(), $object->getKey())->update([
            'status_id' => $statusId,
            'status_updated_at' => Carbon::now(),
        ]);

        return $model;
    }

    public function isForOrder()
    {
        return $this->object_type === Order::make()->getMorphClass();
    }

    //
    //
    //

    public function scopeApplyRelated($query, $model)
    {
        return $query->where('object_type', $model->getMorphClass())
            ->where('object_id', $model->getKey());
    }

    public function scopeWhereStatusIsLatest($query, $statusId)
    {
        return $query->where('status_id', $statusId)->orderBy('created_at', 'desc');
    }
}
