<?php

namespace Igniter\System\Http\Requests;

use Igniter\System\Classes\FormRequest;
use Illuminate\Validation\Rule;

class MailPartialRequest extends FormRequest
{
    public function attributes(): array
    {
        return [
            'name' => lang('igniter::admin.label_name'),
            'code' => lang('igniter::system.mail_templates.label_code'),
            'html' => lang('igniter::system.mail_templates.label_html'),
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'code' => ['sometimes', 'required', 'regex:/^[a-z-_\.\:]+$/i',
                Rule::unique('mail_partials')->ignore($this->getRecordId(), 'partial_id'),
            ],
            'html' => ['required', 'string'],
            'text' => ['nullable', 'string'],
        ];
    }
}
