<?php

namespace Igniter\System\Http\Requests;

use Igniter\System\Classes\FormRequest;
use Illuminate\Validation\Rule;

class MailTemplateRequest extends FormRequest
{
    public function attributes(): array
    {
        return [
            'layout_id' => lang('igniter::system.mail_templates.label_layout'),
            'label' => lang('igniter::admin.label_description'),
            'subject' => lang('igniter::system.mail_templates.label_subject'),
            'code' => lang('igniter::system.mail_templates.label_code'),
        ];
    }

    public function rules(): array
    {
        return [
            'layout_id' => ['integer'],
            'code' => ['sometimes', 'required', 'min:2', 'max:255',
                Rule::unique('mail_templates', 'code')->ignore($this->getRecordId(), 'template_id'),
                'regex:/^[a-z-_\.\:]+$/i',
            ],
            'label' => ['required', 'string'],
            'subject' => ['required', 'string'],
            'body' => ['string'],
            'plain_body' => ['nullable', 'string'],
        ];
    }
}
