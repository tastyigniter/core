<?php

use Illuminate\Support\Facades\Route;

// Register Assets Combiner routes
Route::any(config('igniter.system.assetsCombinerUri', '_assets').'/{asset}', 'Igniter\System\Classes\Controller@combineAssets');

// Other pages
Route::any('/{slug?}', 'Igniter\System\Classes\Controller@runAdmin')
    ->where('slug', '(.*)?');
