<?php

namespace Igniter\System\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Sortable;
use Igniter\System\Classes\HubManager;
use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Concerns\Switchable;

/**
 * Country Model Class
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

    public static function upsertFromHub()
    {
        $response = resolve(HubManager::class)->getDataset('countries');

        collect(array_get($response, 'data', []))->each(function ($item) {
            if (!$country = static::firstWhere('iso_code_3', $item['iso_code_3'])) {
                $item['format'] = '{address_1}\n{address_2}\n{city} {postcode} {state}\n{country}';
                $item['status'] = true;
                $country = static::create($item);
            }

            $country->update($item);
        });
    }
}
