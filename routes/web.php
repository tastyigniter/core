<?php

use Illuminate\Support\Facades\Route;

Route::any(config('igniter.system.assetsCombinerUri', '_assets').'/{asset}', 'Igniter\System\Classes\Controller@combineAssets');

Route::any('{slug}', 'Igniter\System\Classes\Controller@run')
    ->where('slug', '(.*)?');
