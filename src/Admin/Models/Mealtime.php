<?php

namespace Igniter\Admin\Models;

use Carbon\Carbon;
use Igniter\Admin\Traits\Locationable;
use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;

/**
 * Mealtime Model Class
 */
class Mealtime extends Model
{
    use HasFactory;
    use Locationable;

    const LOCATIONABLE_RELATION = 'locations';

    /**
     * @var string The database table name
     */
    protected $table = 'mealtimes';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'mealtime_id';

    protected $casts = [
        'start_time' => 'time',
        'end_time' => 'time',
        'mealtime_status' => 'boolean',
    ];

    public $relation = [
        'morphToMany' => [
            'locations' => [\Igniter\Admin\Models\Location::class, 'name' => 'locationable'],
        ],
    ];

    public $timestamps = true;

    public function getDropdownOptions()
    {
        $this->isEnabled()->dropdown('mealtime_name');
    }

    //
    // Scopes
    //

    public function scopeIsEnabled($query)
    {
        return $query->where('mealtime_status', 1);
    }

    public function isAvailable($datetime = null)
    {
        if (is_null($datetime))
            $datetime = Carbon::now();

        if (!$datetime instanceof Carbon) {
            $datetime = Carbon::parse($datetime);
        }

        return $datetime->between(
            $datetime->copy()->setTimeFromTimeString($this->start_time),
            $datetime->copy()->setTimeFromTimeString($this->end_time)
        );
    }

    public function isAvailableNow()
    {
        return $this->isAvailable();
    }
}
