<?php

namespace Igniter\Admin\Requests;

use Igniter\System\Classes\FormRequest;

class OrderRequest extends FormRequest
{
    protected function useDataFrom()
    {
        return static::DATA_TYPE_POST;
    }

    public function rules()
    {
        return [];
    }
}
