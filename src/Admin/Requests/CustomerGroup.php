<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class CustomerGroup extends FormRequest
{
    public function attributes()
    {
        return [
            'group_name' => lang('igniter::admin.label_name'),
            'approval' => lang('igniter::admin.customer_groups.label_approval'),
            'description' => lang('igniter::admin.label_description'),
        ];
    }

    public function rules()
    {
        return [
            'group_name' => ['required', 'string', 'between:2,32'],
            'approval' => ['required', 'boolean'],
            'description' => ['string', 'between:2,512'],
        ];
    }
}
