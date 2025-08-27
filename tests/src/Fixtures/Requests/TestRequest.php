<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Requests;

use Override;
use Igniter\System\Classes\FormRequest;

class TestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'full name',
        ];
    }
}
