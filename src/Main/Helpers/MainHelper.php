<?php

namespace Igniter\Main\Helpers;

use Igniter\Flame\Pagic\Router;
use Illuminate\Support\Facades\URL;

class MainHelper
{
    public static function url(string|null $path = null, array $params = [])
    {
        if (!$url = resolve(Router::class)->url($path, $params)) {
            $url = $path;
        }

        return URL::to($url);
    }

    public static function pageUrl(string|null $path = null, array $params = [])
    {
        if (!$url = resolve(Router::class)->pageUrl($path, $params)) {
            $url = $path;
        }

        return URL::to($url);
    }
}