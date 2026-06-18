<?php

declare(strict_types=1);

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\MarketplaceException;
use Illuminate\Support\Facades\Lang;

class MarketplaceErrorPresenter
{
    public static function message(MarketplaceException $exception): string
    {
        $errorCode = $exception->errorCode;
        $translationKey = 'igniter::system.updates.marketplace_errors.'.$errorCode;

        if ($errorCode && Lang::has($translationKey)) {
            return lang($translationKey);
        }

        return $exception->getMessage();
    }
}
