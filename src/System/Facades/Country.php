<?php

namespace Igniter\System\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string addressFormat(\Illuminate\Database\Eloquent\Model|array $address, bool $useLineBreaks = true)
 * @method static string|null getCountryNameById(string|int|null $id = null)
 * @method static string|null getCountryCodeById(string|int|null $id = null, string|null $codeType = null)
 * @method static string|null getCountryNameByCode(string $isoCodeTwo)
 * @method static string getDefaultFormat()
 * @method static \Illuminate\Support\Collection listAll(string|null $column = null, string $key = 'country_id')
 *
 * @see \Igniter\System\Libraries\Country
 */
class Country extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     * @see \Igniter\System\Libraries\Country
     */
    protected static function getFacadeAccessor()
    {
        return 'country';
    }
}
