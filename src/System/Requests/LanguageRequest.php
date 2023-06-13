<?php

namespace Igniter\System\Requests;

use Igniter\System\Classes\FormRequest;
use Illuminate\Validation\Rule;

class LanguageRequest extends FormRequest
{
    public function attributes()
    {
        return [
            'name' => lang('igniter::admin.label_name'),
            'code' => lang('igniter::system.languages.label_code'),
            'status' => lang('igniter::admin.label_status'),
            'translations.*.source' => lang('igniter::system.column_source'),
            'translations.*.translation' => lang('igniter::system.column_translation'),
        ];
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'between:2,32'],
            'code' => ['required', 'regex:/^[a-zA-Z_]+$/',
                Rule::unique('languages')->ignore($this->getRecordId(), 'language_id'),
            ],
            'status' => ['required', 'boolean'],
            'translations.*.source' => ['string', 'max:2500'],
            'translations.*.translation' => ['string', 'max:2500'],
        ];
    }
}
