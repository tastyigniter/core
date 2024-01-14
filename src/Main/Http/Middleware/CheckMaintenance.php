<?php

namespace Igniter\Main\Http\Middleware;

use Igniter\Flame\Igniter;
use Igniter\User\Facades\AdminAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class CheckMaintenance
{
    public function handle(Request $request, \Closure $next): mixed
    {
        if (!Igniter::runningInAdmin() && setting('maintenance_mode') && !AdminAuth::isLogged()) {
            return Response::make(
                View::make('igniter.system::maintenance', ['message' => setting('maintenance_message')]),
                503
            );
        }

        return $next($request);
    }
}