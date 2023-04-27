<?php

namespace Igniter\Main\Http\Middleware;

use Igniter\Admin\Facades\AdminAuth;
use Igniter\Flame\Igniter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class CheckMaintenance
{
    public function handle($request, \Closure $next)
    {
        if (!Igniter::runningInAdmin() && setting('maintenance_mode') && !AdminAuth::isLogged()) {
            return Response::make(
                View::make('igniter.main::maintenance', ['message' => setting('maintenance_message')]),
                503
            );
        }

        return $next($request);
    }
}