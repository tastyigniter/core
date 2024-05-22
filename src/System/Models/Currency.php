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
        'symbol_position' => 'boolean',
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

    public function getSymbolPosition(): ?string
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

    public function getRate(): ?string
    {
        return $this->currency_rate;
    }
}
