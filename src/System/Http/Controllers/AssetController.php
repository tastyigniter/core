<?php

declare(strict_types=1);

namespace Igniter\System\Http\Controllers;

use Igniter\System\Facades\Assets;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class AssetController extends Controller
{
    public function __invoke(Request $request, ?string $asset = null): Response
    {
        // Combined assets are served as "app.{type}?v={cacheKey}-{lastMod}",
        // legacy URLs as "{cacheKey}-{lastMod}.{type}".
        $parts = explode('-', (string)($request->query('v') ?: $asset));
        $cacheKey = $parts[0];

        return Assets::combineGetContents($cacheKey);
    }
}
