<?php

namespace Igniter\Main\Providers;

use Igniter\Main\Template\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PagicServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Route::bind('_file_', function($value) {
            return Page::resolveRouteBinding($value);
        });
    }
}
