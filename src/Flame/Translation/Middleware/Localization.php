<?php

namespace Igniter\Flame\Translation\Middleware;

use Closure;
use Igniter\Igniter;
use Illuminate\Http\Request;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Igniter::hasDatabase())
            return $next($request);

        Igniter::runningInAdmin()
            ? $this->loadAdminLocale()
            : $this->loadLocale();

        return $next($request);
    }

    protected function loadAdminLocale()
    {
        $localization = app('translator.localization');

        $sessionLocale = $localization->getSessionLocale();
        $userLocale = $this->getUserLocale() ?? $localization->getDefaultLocale();

        $storeSession = $sessionLocale !== $userLocale;

        $localization->setLocale($userLocale, $storeSession);
    }

    protected function loadLocale()
    {
        $localization = app('translator.localization');

        if ($localization->loadLocaleFromRequest())
            return;

        if ($localization->loadLocaleFromBrowser())
            return;

        if ($localization->loadLocaleFromSession())
            return;

        $localization->setLocale($localization->getDefaultLocale());
    }

    protected function getUserLocale()
    {
        if (!app('admin.auth')->isLogged())
            return null;

        return app('admin.auth')->user()->getLocale();
    }
}
