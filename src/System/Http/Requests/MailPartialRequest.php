<?php

declare(strict_types=1);

namespace Igniter\System\Http\Requests;

use Igniter\System\Classes\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class MailPartialRequest extends FormRequest
{
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => lang('igniter::admin.label_name'),
            'code' => lang('igniter::system.mail_templates.label_code'),
            'params.*.name' => lang('igniter::system.mail_builder.label_param_name'),
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'code' => ['sometimes', 'required', 'regex:/^[a-z-_\.\:]+$/i',
                Rule::unique('mail_partials')->ignore($this->getRecordId(), 'partial_id'),
            ],
            'params' => ['nullable', 'array'],
            'params.*.name' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z_][A-Za-z0-9_]*$/', 'not_in:slot,body'],
            'params.*.label' => ['nullable', 'string', 'max:128'],
            'params.*.sample' => ['nullable', 'string', 'max:255'],
        ];
    }
}
