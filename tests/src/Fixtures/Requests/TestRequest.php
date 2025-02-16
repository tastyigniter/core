<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Requests;

use Igniter\System\Classes\FormRequest;

class TestRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'full name',
        ];
    }
}
