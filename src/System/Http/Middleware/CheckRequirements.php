<?php

namespace Igniter\System\Http\Middleware;

use Igniter\Flame\Igniter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class CheckRequirements
{
    public function handle(Request $request, \Closure $next)
    {
        if (!Igniter::hasDatabase()) {
            return Response::make(View::make('igniter.system::no_database'));
        }

        return $next($request);
    }
}