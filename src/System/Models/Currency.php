<?php

namespace Igniter\System\Models;

use Igniter\Flame\Currency\Contracts\CurrencyInterface;
use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\ValidationException;

/**
 * Currency Model Class
 */
class Currency extends Model implements CurrencyInterface
{
    use HasFactory;

    /**
     * @var string The database table name
     */
    protected $table = 'currencies';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'currency_id';

    /**
     * @var array The model table column to convert to dates on insert/update
     */
    public $timestamps = true;

    protected $casts = [
        'country_id' => 'integer',
        'currency_rate' => 'float',
        'symbol_position' => 'boolean',
        'currency_status' => 'boolean',
    ];

    public $relation = [
        'belongsTo' => [
            'country' => \Igniter\System\Models\Country::class,
        ],
    ];

    /**
     * @var self Default currency cache.
     */
    protected static $defaultCurrency;

    public static $allowedSortingColumns = ['currency_name asc', 'currency_name desc', 'currency_code asc', 'currency_code desc'];

    public function scopeListFrontEnd($query, $options = [])
    {
        extract(array_merge([
            'page' => 1,
            'pageLimit' => 20,
            'enabled' => true,
            'sort' => 'currency_name asc',
            'search' => '',
        ], $options));

        $searchableFields = ['currency_name', 'currency_code'];

        if (!is_array($sort)) {
            $sort = [$sort];
        }

        foreach ($sort as $_sort) {
            if (in_array($_sort, self::$allowedSortingColumns)) {
                $parts = explode(' ', $_sort);
                if (count($parts) < 2) {
                    $parts[] = 'desc';
                }
                [$sortField, $sortDirection] = $parts;
                $query->orderBy($sortField, $sortDirection);
            }
        }

        $search = trim($search);
        if (strlen($search)) {
            $query->search($search, $searchableFields);
        }

        if ($enabled) {
            $query->isEnabled();
        }

        $this->fireEvent('model.extendListFrontEndQuery', [$query]);

        return $query->paginate($pageLimit, $page);
    }

    public function scopeIsEnabled($query)
    {
        return $query->where('currency_status', 1);
    }

    public function makeDefault()
    {
        if (!$this->currency_status) {
            throw new ValidationException(['currency_status' => sprintf(
                lang('igniter::admin.alert_error_set_default'), $this->currency_name
            )]);
        }

        setting('default_currency_code', $this->currency_code);
        setting()->save();
    }

    /**
     * Returns the default currency defined.
     * @return self
     */
    public static function getDefault()
    {
        if (self::$defaultCurrency !== null) {
            return self::$defaultCurrency;
        }

        $defaultCurrency = self::whereIsEnabled()->where('currency_id', setting('default_currency_code'))
            ->orWhere('currency_code', setting('default_currency_code'))
            ->first();

        if (!$defaultCurrency) {
            if ($defaultCurrency = self::whereIsEnabled()->first()) {
                $defaultCurrency->makeDefault();
            }
        }

        return self::$defaultCurrency = $defaultCurrency;
    }

    public static function getDropdownOptions()
    {
        return static::select(['currency_id', 'currencies.country_id', 'priority'])
            ->selectRaw("CONCAT_WS(' - ', country_name, currency_code, currency_symbol) as name")
            ->leftJoin('countries', 'currencies.country_id', '=', 'countries.country_id')
            ->orderBy('priority')
            ->where('currency_status', 1)
            ->dropdown('name', 'currency_id');
    }

    public static function getConverterDropdownOptions()
    {
        return [
            'openexchangerates' => 'lang:igniter::system.settings.text_openexchangerates',
            'fixerio' => 'lang:igniter::system.settings.text_fixerio',
        ];
    }

    protected function afterSave()
    {
        app('currency')->clearCache();
    }

    protected function afterDelete()
    {
        app('currency')->clearCache();
    }

    public function updateRate($currencyRate)
    {
        $this->currency_rate = $currencyRate;
        $this->save();
    }

    public function scopeWhereIsEnabled($query)
    {
        return $query->where('currency_status', 1);
    }

    //
    //
    //

    public function getId()
    {
        return $this->currency_id;
    }

    public function getName()
    {
        return $this->currency_name;
    }

    public function getCode()
    {
        return $this->currency_code;
    }

    public function getSymbol()
    {
        return $this->currency_symbol;
    }

    public function getSymbolPosition()
    {
        return $this->symbol_position;
    }

    public function getFormat()
    {
        $format = ($this->thousand_sign ?: '!').'0'.$this->decimal_sign;
        $format .= str_repeat('0', $this->decimal_position);

        return $this->getSymbolPosition()
            ? '1'.$format.$this->getSymbol()
            : $this->getSymbol().'1'.$format;
    }

    public function getRate()
    {
        return $this->currency_rate;
    }

    public function isEnabled()
    {
        return $this->currency_status;
    }
}
