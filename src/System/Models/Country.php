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
 * @method static \Igniter\Flame\Database\Builder<static>|Country applyFilters(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Country applySorts(array $sorts = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Country applySwitchable(bool $switch = true)
 * @method static \Igniter\Flame\Database\Builder<static>|Country dropdown(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Country isEnabled()
 * @method static \Igniter\Flame\Database\Builder<static>|Country like(string $column, string $value, string $side = 'both', string $boolean = 'and')
 * @method static \Igniter\Flame\Database\Builder<static>|Country listFrontEnd(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Country lists(string $column, string $key = null)
 * @method static \Igniter\Flame\Database\Builder<static>|Country newModelQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Country newQuery()
 * @method static \Igniter\Flame\Database\Builder<static>|Country orLike(string $column, string $value, string $side = 'both')
 * @method static \Igniter\Flame\Database\Builder<static>|Country orSearch(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|Country pluckDates(string $column, string $keyFormat = 'Y-m', string $valueFormat = 'F Y')
 * @method static \Igniter\Flame\Database\Builder<static>|Country query()
 * @method static \Igniter\Flame\Database\Builder<static>|Country search(string $term, string $columns = [], string $mode = 'all')
 * @method static \Igniter\Flame\Database\Builder<static>|Country sorted()
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereCountryId($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereCountryName($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereCreatedAt($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereFormat($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereIsDefault($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereIsDisabled()
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereIsEnabled()
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereIsoCode2($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereIsoCode3($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereNotDefault()
 * @method static \Igniter\Flame\Database\Builder<static>|Country wherePriority($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereStatus($value)
 * @method static \Igniter\Flame\Database\Builder<static>|Country whereUpdatedAt($value)
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
