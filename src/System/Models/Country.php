<?php

namespace Igniter\System\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Sortable;
use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Concerns\Switchable;

/**
 * Country Model Class
 *
 * @property int $country_id
 * @property string $country_name
 * @property string|null $iso_code_2
 * @property string|null $iso_code_3
 * @property string|null $format
 * @property int $status
 * @property int $priority
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_default
 * @method static \Igniter\Flame\Database\Builder<static>|Country applyDefaultable(bool $default = true)
 * @method static \Igniter\Flame\Database\Builder<static>|Country applySwitchable(bool $switch = true)
 * @method static \Igniter\Flame\Database\Builder<static>|Country listFrontEnd(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Country query()
 * @method static \Igniter\Flame\Database\Builder<static>|Country sorted()
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereIsEnabled()
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Country extends Model
{
    use Defaultable;
    use HasFactory;
    use Sortable;
    use Switchable;

    public const SORT_ORDER = 'priority';

    /**
     * @var string The database table name
     */
    protected $table = 'countries';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'country_id';

    protected $guarded = [];

    protected $casts = [
        'priority' => 'integer',
    ];

    public $relation = [
        'hasOne' => [
            'currency' => \Igniter\System\Models\Currency::class,
        ],
    ];

    public $timestamps = true;

    public static function getDropdownOptions()
    {
        return static::whereIsEnabled()->dropdown('country_name');
    }

    public function defaultableName()
    {
        return $this->country_name;
    }
}
