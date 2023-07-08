<?php

namespace Igniter\System\Http\Controllers;

use Igniter\System\Facades\Assets;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AssetController extends Controller
{
    public function __invoke(Request $request, $asset = null)
    {
        $parts = explode('-', $asset);
        $cacheKey = $parts[0];

        return Assets::combineGetContents($cacheKey);
    }
}
