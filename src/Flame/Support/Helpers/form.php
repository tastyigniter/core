<?php

/**
 * Form helper functions
 */

use Igniter\Flame\Html\FormBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ViewErrorBag;

if (!function_exists('form_open')) {
    /**
     * Form Declaration
     * Creates the opening portion of the form.
     *
     * @param string $action the URI segments of the form destination
     * @param array $attributes a key/value a pair of attributes
     *
     * @return    string
     */
    function form_open($action = null, $attributes = [])
    {
        if (is_string($action)) {
            $attributes['url'] = $action;
        } else {
            $attributes = $action;
        }

        $handler = null;
        if (isset($attributes['handler'])) {
            $handler = app(FormBuilder::class)->hidden('_handler', $attributes['handler']);
        }

        return app(FormBuilder::class)->open($attributes).$handler;
    }
}

if (!function_exists('form_open_multipart')) {
    /**
     * Form Declaration - Multipart type
     * Creates the opening portion of the form, but with "multipart/form-data".
     *
     * @param string $action the URI segments of the form destination
     * @param array $attributes a key/value pair of attributes
     *
     * @return    string
     */
    function form_open_multipart($action = '', $attributes = [])
    {
        $attributes['enctype'] = 'multipart/form-data';

        return form_open($action, $attributes);
    }
}

if (!function_exists('form_close')) {
    /**
     * Form Close Tag
     *
     * @param string $extra
     *
     * @return    string
     */
    function form_close($extra = '')
    {
        return app(FormBuilder::class)->close().$extra;
    }
}

if (!function_exists('set_value')) {
    /**
     * Form Value
     * Grabs a value from the POST array for the specified field so you can
     * re-populate an input field or textarea. If Form Validation
     * is active it retrieves the info from the validation class
     *
     * @param string $field Field name
     * @param string $default Default value
     *
     * @return    string
     */
    function set_value($field, $default = '')
    {
        return app(FormBuilder::class)->getValueAttribute($field, $default);
    }
}

if (!function_exists('set_select')) {
    /**
     * Set Select
     * Lets you set the selected value of a <select> menu via data in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     *
     * @param $field string
     * @param $value string
     * @param $default bool
     *
     * @return    string
     */
    function set_select($field, $value = '', $default = false)
    {
        if (($input = set_value($field, false)) === null) {
            return ($default === true) ? ' selected="selected"' : '';
        }

        $value = (string)$value;
        if (is_array($input)) {
            if (in_array($value, $input, true)) {
                return ' selected="selected"';
            }

            return '';
        }

        return ($input === $value) ? ' selected="selected"' : '';
    }
}

if (!function_exists('set_checkbox')) {
    /**
     * Set Checkbox
     * Lets you set the selected value of a checkbox via the value in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     *
     * @param $field string
     * @param $value string
     * @param $default bool
     *
     * @return    string
     */
    function set_checkbox($field, $value = '', $default = false)
    {
        // Form inputs are always strings ...
        $value = (string)$value;
        $input = set_value($field, false);

        if (is_array($input)) {
            if (in_array($value, $input, true)) {
                return ' checked="checked"';
            }

            return '';
        } elseif (is_string($input)) {
            return ($input === $value) ? ' checked="checked"' : '';
        }

        return ($default === true) ? ' checked="checked"' : '';
    }
}

if (!function_exists('set_radio')) {
    /**
     * Set Radio
     * Let's you set the selected value of a radio field via info in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     *
     * @param string $field
     * @param string $value
     * @param bool $default
     *
     * @return    string
     */
    function set_radio($field, $value = '', $default = false)
    {
        // Form inputs are always strings ...
        $value = (string)$value;
        $input = set_value($field, false);

        if (is_array($input)) {
            if (in_array($value, $input, true)) {
                return ' checked="checked"';
            }

            return '';
        } elseif (is_string($input)) {
            return ($input === $value) ? ' checked="checked"' : '';
        }

        return ($default === true) ? ' checked="checked"' : '';
    }
}

if (!function_exists('form_error')) {
    /**
     * Form Error
     * Returns the error for a specific form field. This is a helper for the
     * form validation class.
     */
    function form_error($field = null, $prefix = '', $suffix = '', $bag = 'default')
    {
        $errors = (Config::get('session.driver') && Session::has('errors'))
            ? Session::get('errors')
            : array_get(app('view')->getShared(), 'errors', new \Illuminate\Support\ViewErrorBag);

        $errors = $errors->getBag($bag);

        if (is_null($field)) {
            return $errors;
        }

        if (!$errors->has($field)) {
            return null;
        }

        return $prefix.$errors->first($field).$suffix;
    }
}

if (!function_exists('has_form_error')) {
    /**
     * Form Error
     * Returns the error for a specific form field. This is a helper for the
     * form validation class.
     *
     * @return    string
     */
    function has_form_error($field = null, $bag = 'default')
    {
        $errors = (Config::get('session.driver') && Session::has('errors'))
            ? Session::get('errors')
            : array_get(app('view')->getShared(), 'errors', new ViewErrorBag);

        $errors = $errors->getBag($bag);

        if (is_null($field)) {
            return $errors;
        }

        return $errors->has($field);
    }
}
