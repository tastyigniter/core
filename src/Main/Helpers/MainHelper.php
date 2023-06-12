<?php

namespace Igniter\Main\Helpers;

use Igniter\Flame\Pagic\Router;
use Illuminate\Support\Facades\URL;

class MainHelper
{
    public static function pageUrl(string|null $path = null, array $params = [])
    {
        if (!is_null($path)) {
            $path = resolve(Router::class)->pageUrl($path, $params);
        }

        return URL::to($path);
    }
}
