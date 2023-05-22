<?php

namespace Igniter\System\Requests;

use Igniter\System\Classes\FormRequest;

class GeneralSettingsRequest extends FormRequest
{
    public function attributes()
    {
        return [
            'site_name' => lang('igniter::system.settings.label_site_name'),
            'site_email' => lang('igniter::system.settings.label_site_email'),
            'site_logo' => lang('igniter::system.settings.label_site_logo'),
            'maps_api_key' => lang('igniter::system.settings.label_maps_api_key'),
            'distance_unit' => lang('igniter::system.settings.label_distance_unit'),

            'timezone' => lang('igniter::system.settings.label_timezone'),
            'detect_language' => lang('igniter::system.settings.label_detect_language'),
            'country_id' => lang('igniter::system.settings.label_country'),
        ];
    }

    public function rules()
    {
        return [
            'site_name' => ['required', 'string', 'min:2', 'max:255'],
            'site_email' => ['required', 'email:filter', 'max:96'],
            'site_logo' => ['required', 'string'],
            'distance_unit' => ['required', 'in:mi,km'],
            'default_geocoder' => ['required', 'in:nominatim,google,chain'],
            'maps_api_key' => ['required_if:default_geocoder,google', 'alpha_dash'],
            'menus_page' => ['required', 'string'],
            'reservation_page' => ['required', 'string'],

            'timezone' => ['required', 'timezone'],
            'detect_language' => ['required', 'boolean'],
        ];
    }
}
