<?php

namespace Igniter\Admin\Models;

use Carbon\Carbon;
use Igniter\Admin\Traits\Locationable;
use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\System\Models\Concerns\Switchable;

/**
 * Mealtime Model Class
 */
class Mealtime extends Model
{
    use HasFactory;
    use Locationable;
    use Switchable;

    public const LOCATIONABLE_RELATION = 'locations';

    public const SWITCHABLE_COLUMN = 'mealtime_status';

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
    ];

    public $relation = [
        'morphToMany' => [
            'locations' => [\Igniter\Admin\Models\Location::class, 'name' => 'locationable'],
        ],
    ];

    public $timestamps = true;

    public function getDropdownOptions()
    {
        $this->whereIsEnabled()->dropdown('mealtime_name');
    }

    //
    // Scopes
    //

    public function isAvailable($datetime = null)
    {
        if (is_null($datetime)) {
            $datetime = Carbon::now();
        }

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
