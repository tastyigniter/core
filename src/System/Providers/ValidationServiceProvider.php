<?php

namespace Igniter\System\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Validator::extend('trim', function ($attribute, $value, $parameters, $validator) {
            return trim($value);
        });

        Validator::extend('valid_date', function ($attribute, $value, $parameters, $validator) {
            return !(!preg_match('/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/', $value)
                && !preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $value));
        });

        Validator::extend('valid_time', function ($attribute, $value, $parameters, $validator) {
            return !(!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value)
                && !preg_match('/^(1[012]|[1-9]):[0-5][0-9](\s)?(?i)(am|pm)$/', $value));
        });
    }
}