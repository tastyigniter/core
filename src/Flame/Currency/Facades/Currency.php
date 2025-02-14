<?php

declare(strict_types=1);

namespace Igniter\Flame\Currency\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string|int|float|null convert(string|int|float $amount, string|null $from = null, string|null $to = null, bool $format = true)
 * @method static string format(string|float $value, string|null $code = null, bool $includeSymbol = true)
 * @method static array formatToJson(float $value, string|null $code = null)
 * @method static void setUserCurrency(string $code)
 * @method static string getUserCurrency()
 * @method static bool hasCurrency(string $code)
 * @method static bool isActive(string $code)
 * @method static \Igniter\Flame\Currency\Contracts\CurrencyInterface getCurrency(string|null $code = null)
 * @method static \Illuminate\Support\Collection getCurrencies()
 * @method static \Igniter\Flame\Currency\Contracts\CurrencyInterface getModel()
 * @method static \Igniter\Flame\Currency\Contracts\FormatterInterface|null getFormatter()
 * @method static void clearCache()
 * @method static mixed config(string|null $key = null, mixed $default = null)
 * @method static void updateRates(bool $skipCache = false)
 *
 * @see \Igniter\Flame\Currency\Currency
 */
class Currency extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'currency';
    }
}
