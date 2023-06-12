<?php

namespace Igniter\Admin\Traits;

use Exception;
use Igniter\Admin\Facades\AdminHelper;
use Igniter\System\Exception\ErrorHandler;
use Illuminate\Support\Facades\Redirect;

trait ControllerHelpers
{
    public function pageUrl($path = null, $parameters = [], $secure = null)
    {
        return AdminHelper::url($path, $parameters, $secure);
    }

    public function redirect($path, $status = 302, $headers = [], $secure = null)
    {
        return AdminHelper::redirect($path, $status, $headers, $secure);
    }

    public function redirectGuest($path, $status = 302, $headers = [], $secure = null)
    {
        return AdminHelper::redirectGuest($path, $status, $headers, $secure);
    }

    public function redirectIntended($path, $status = 302, $headers = [], $secure = null)
    {
        return AdminHelper::redirectIntended($path, $status, $headers, $secure);
    }

    public function redirectBack($status = 302, $headers = [], $fallback = false)
    {
        return Redirect::back($status, $headers, AdminHelper::url($fallback ?: 'dashboard'));
    }

    public function refresh()
    {
        return Redirect::back();
    }

    /**
     * Sets standard page variables in the case of a controller error.
     *
     * @throws \Exception
     */
    public function handleError(Exception $exception)
    {
        $errorMessage = ErrorHandler::getDetailedMessage($exception);
        $this->fatalError = $errorMessage;
        $this->vars['fatalError'] = $errorMessage;

        flash()->error($errorMessage)->important();
    }
}
