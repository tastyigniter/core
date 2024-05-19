<?php

namespace Igniter\System\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class PaginationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Paginator::useBootstrap();

        Paginator::defaultView('igniter.system::_partials/pagination/default');
        Paginator::defaultSimpleView('igniter.system::_partials/pagination/simple_default');

        Paginator::currentPathResolver(function() {
            return url()->current();
        });

        Paginator::currentPageResolver(function($pageName = 'page') {
            $page = Request::get($pageName);
            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int)$page >= 1) {
                return $page;
            }

            return 1;
        });
    }
}