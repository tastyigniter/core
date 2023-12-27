<?php

namespace Igniter\Admin\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;

/**
 * Status Model Class
 */
class Status extends Model
{
    use HasFactory;

    /**
     * @var string The database table name
     */
    protected $table = 'statuses';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'status_id';

    protected $casts = [
        'notify_customer' => 'boolean',
    ];

    public $relation = [
        'hasMany' => [
            'status_history' => \Igniter\Admin\Models\StatusHistory::class,
        ],
    ];

    public $timestamps = true;

    /**
     * Return status_for attribute as lang text, used by
     *
     * @param $row
     *
     * @return string
     */
    public function getStatusForNameAttribute($value)
    {
        return ($this->status_for == 'reservation') ? lang('igniter::admin.statuses.text_reservation') : lang('igniter::admin.statuses.text_order');
    }

    public function getStatusForDropdownOptions()
    {
        return [
            'order' => lang('igniter::admin.statuses.text_order'),
            'reservation' => lang('igniter::admin.statuses.text_reservation'),
        ];
    }

    public static function getDropdownOptionsForOrder()
    {
        return static::isForOrder()->dropdown('status_name');
    }

    public static function getDropdownOptionsForReservation()
    {
        return static::isForReservation()->dropdown('status_name');
    }

    //
    // Scopes
    //

    /**
     * Scope a query to only include order statuses
     *
     * @return $this
     */
    public function scopeIsForOrder($query)
    {
        return $query->where('status_for', 'order');
    }

    /**
     * Scope a query to only include reservation statuses
     *
     * @return $this
     */
    public function scopeIsForReservation($query)
    {
        return $query->where('status_for', 'reservation');
    }

    //
    // Helpers
    //

    public static function listStatuses()
    {
        return static::all()->keyBy('status_id');
    }
}
