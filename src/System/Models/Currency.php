<?php

namespace Igniter\System\Models;

use Igniter\Flame\Currency\Contracts\CurrencyInterface;
use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Concerns\HasCountry;
use Igniter\System\Models\Concerns\Switchable;

/**
 * Currency Model Class
 *
 * @property int $currency_id
 * @property int $country_id
 * @property string $currency_name
 * @property string $currency_code
 * @property string $currency_symbol
 * @property float $currency_rate
 * @property int|null $symbol_position
 * @property string $thousand_sign
 * @property string $decimal_sign
 * @property int $decimal_position
 * @property string|null $iso_alpha2
 * @property string|null $iso_alpha3
 * @property int|null $iso_numeric
 * @property int|null $currency_status
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property bool $is_default
 * @method static \Igniter\Flame\Database\Builder<static>|Currency applyDefaultable(bool $default = true)
 * @method static \Igniter\Flame\Database\Builder<static>|Currency applySwitchable(bool $switch = true)
 * @method static \Igniter\Flame\Database\Builder<static>|Currency listFrontEnd(array $options = [])
 * @method static \Igniter\Flame\Database\Builder<static>|Currency query()
 * @method static \Igniter\Flame\Database\Builder<static>|Currency whereIsEnabled()
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Currency extends Model implements CurrencyInterface
{
    use Defaultable;
    use HasCountry;
    use HasFactory;
    use Switchable;

    const SWITCHABLE_COLUMN = 'currency_status';

    /**
     * @var string The database table name
     */
    protected $table = 'currencies';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'currency_id';

    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'country_id' => 'integer',
        'currency_rate' => 'float',
        'symbol_position' => 'integer',
    ];

    public $relation = [
        'belongsTo' => [
            'country' => \Igniter\System\Models\Country::class,
        ],
    ];

    protected array $queryModifierFilters = [
        'enabled' => 'applySwitchable',
    ];

    protected array $queryModifierSorts = ['currency_name asc', 'currency_name desc', 'currency_code asc', 'currency_code desc'];

    protected array $queryModifierSearchableFields = ['currency_name', 'currency_code'];

    public function getDefaultableName()
    {
        return $this->currency_name;
    }

    public static function getDropdownOptions()
    {
        return static::select(['currency_id', 'currencies.country_id', 'priority'])
            ->selectRaw("CONCAT_WS(' - ', country_name, currency_code, currency_symbol) as name")
            ->leftJoin('countries', 'currencies.country_id', '=', 'countries.country_id')
            ->orderBy('priority')
            ->whereIsEnabled()
            ->dropdown('name', 'currency_id');
    }

    public static function getConverterDropdownOptions()
    {
        return [
            'openexchangerates' => 'lang:igniter::system.settings.text_openexchangerates',
            'fixerio' => 'lang:igniter::system.settings.text_fixerio',
        ];
    }

    public function updateRate($rate)
    {
        $this->currency_rate = $rate;
        $this->save();
    }

    //
    //
    //

    public function getId(): ?int
    {
        return $this->currency_id;
    }

    public function getName(): ?string
    {
        return $this->currency_name;
    }

    public function getCode(): ?string
    {
        return $this->currency_code;
    }

    public function getSymbol(): ?string
    {
        return $this->currency_symbol;
    }

    public function getSymbolPosition(): ?int
    {
        return $this->symbol_position;
    }

    public function getFormat(): string
    {
        $format = ($this->thousand_sign ?: '!').'0'.$this->decimal_sign;
        $format .= str_repeat('0', $this->decimal_position);

        return $this->getSymbolPosition()
            ? '1'.$format.$this->getSymbol()
            : $this->getSymbol().'1'.$format;
    }

    public function getRate(): ?float
    {
        return $this->currency_rate;
    }
}
