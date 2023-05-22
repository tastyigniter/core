<?php

namespace Igniter\Admin\Models;

use Igniter\Admin\Traits\HasDeliveryAreas;
use Igniter\Admin\Traits\HasLocationOptions;
use Igniter\Admin\Traits\HasWorkingHours;
use Igniter\Admin\Traits\LocationHelpers;
use Igniter\Flame\Database\Attach\HasMedia;
use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\HasPermalink;
use Igniter\Flame\Database\Traits\Purgeable;
use Igniter\Flame\Location\Contracts\LocationInterface;
use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Concerns\HasCountry;
use Igniter\System\Models\Concerns\Switchable;

/**
 * Location Model Class
 */
class Location extends Model implements LocationInterface
{
    use HasCountry;
    use HasWorkingHours;
    use HasDeliveryAreas;
    use HasFactory;
    use HasPermalink;
    use HasMedia;
    use HasLocationOptions;
    use LocationHelpers;
    use Purgeable;
    use Switchable;
    use Defaultable;

    public const SWITCHABLE_COLUMN = 'location_status';

    const KM_UNIT = 111.13384;

    const M_UNIT = 69.05482;

    const OPENING = 'opening';

    const RESERVATION = 'reservation';

    const DELIVERY = 'delivery';

    const COLLECTION = 'collection';

    const LOCATION_CONTEXT_SINGLE = 'single';

    const LOCATION_CONTEXT_MULTIPLE = 'multiple';

    /**
     * @var string The database table name
     */
    protected $table = 'locations';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'location_id';

    public $timestamps = true;

    protected $appends = ['location_thumb'];

    protected $casts = [
        'location_country_id' => 'integer',
        'location_lat' => 'double',
        'location_lng' => 'double',
    ];

    public $relation = [
        'hasMany' => [
            'all_options' => [\Igniter\Admin\Models\LocationOption::class, 'delete' => true],
            'working_hours' => [\Igniter\Admin\Models\WorkingHour::class, 'delete' => true],
        ],
        'belongsTo' => [
            'country' => [\Igniter\System\Models\Country::class, 'otherKey' => 'country_id', 'foreignKey' => 'location_country_id'],
        ],
        'morphedByMany' => [
            'users' => [\Igniter\Admin\Models\User::class, 'name' => 'locationable'],
            'tables' => [\Igniter\Admin\Models\Table::class, 'name' => 'locationable'],
        ],
    ];

    protected $purgeable = ['options'];

    public $permalinkable = [
        'permalink_slug' => [
            'source' => 'location_name',
            'controller' => 'local',
        ],
    ];

    public $mediable = [
        'thumb',
        'gallery' => ['multiple' => true],
    ];

    protected array $queryModifierFilters = [
        'enabled' => 'applySwitchable',
        'position' => 'applyPosition',
    ];

    protected array $queryModifierSorts = [
        'distance asc', 'distance desc',
        'location_id asc', 'location_id desc',
        'location_name asc', 'location_name desc',
    ];

    protected array $queryModifierSearchableFields = [
        'location_name', 'location_address_1',
        'location_address_2', 'location_city',
        'location_state', 'location_postcode',
        'description',
    ];

    public $url;

    public static function getDropdownOptions()
    {
        return static::whereIsEnabled()->dropdown('location_name');
    }

    public static function onboardingIsComplete()
    {
        if (!$defaultId = self::getDefaultKey()) {
            return false;
        }

        if (!$model = self::whereIsEnabled()->find($defaultId)) {
            return false;
        }

        return isset($model->getAddress()['location_lat'])
            && isset($model->getAddress()['location_lng'])
            && ($model->hasDelivery() || $model->hasCollection())
            && isset($model->options['hours'])
            && $model->delivery_areas()->whereIsDefault()->count() > 0;
    }

    //
    // Accessors & Mutators
    //

    public function getLocationThumbAttribute()
    {
        return $this->hasMedia() ? $this->getThumb() : null;
    }

    public function getDeliveryTimeAttribute($value)
    {
        return (int)$this->getOption('delivery_time_interval');
    }

    public function getCollectionTimeAttribute($value)
    {
        return (int)$this->getOption('collection_time_interval');
    }

    public function getFutureOrdersAttribute($value)
    {
        return (bool)$value;
    }

    public function getReservationTimeIntervalAttribute($value)
    {
        return (int)$this->getOption('reservation_time_interval');
    }

    //
    // Helpers
    //

    public function setUrl($suffix = null)
    {
        if (is_single_location()) {
            $suffix = '/menus';
        }

        $this->url = site_url($this->permalink_slug.$suffix);
    }

    public function hasGallery()
    {
        return $this->hasMedia('gallery');
    }

    public function getGallery()
    {
        $gallery = array_get($this->options, 'gallery');
        $gallery['images'] = $this->getMedia('gallery');

        return $gallery;
    }

    public function allowGuestOrder()
    {
        if (($allowGuestOrder = (int)$this->getOption('guest_order', -1)) === -1) {
            $allowGuestOrder = (int)setting('guest_order', 1);
        }

        return (bool)$allowGuestOrder;
    }

    public function listAvailablePayments()
    {
        $result = [];

        $payments = array_get($this->options, 'payments', []);
        $paymentGateways = Payment::listPayments();

        foreach ($paymentGateways as $payment) {
            if ($payments && !in_array($payment->code, $payments)) {
                continue;
            }

            $result[$payment->code] = $payment;
        }

        return collect($result);
    }

    public function getDefaultableName()
    {
        return $this->location_name;
    }
}
