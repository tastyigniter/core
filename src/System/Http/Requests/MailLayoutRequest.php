<?php

namespace Igniter\System\Http\Requests;

use Igniter\System\Classes\FormRequest;
use Illuminate\Validation\Rule;

class MailLayoutRequest extends FormRequest
{
    public function attributes(): array
    {
        return [
            'name' => lang('igniter::admin.label_name'),
            'code' => lang('igniter::system.mail_templates.label_code'),
            'layout' => lang('igniter::system.mail_templates.label_body'),
            'layout_css' => lang('igniter::system.mail_templates.label_layout_css'),
            'plain_layout' => lang('igniter::system.mail_templates.label_plain'),
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'between:2,32'],
            'code' => ['sometimes', 'required', 'regex:/^[a-z-_\.\:]+$/i',
                Rule::unique('mail_layouts')->ignore($this->getRecordId(), 'layout_id'),
            ],
            'layout' => ['string'],
            'layout_css' => ['nullable', 'string'],
            'plain_layout' => ['nullable', 'string'],
        ];
    }
}
