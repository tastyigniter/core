<?php

namespace Igniter\Tests\Flame\Database\Fixtures;

use Igniter\Flame\Database\Traits\Validation;
use Igniter\System\Models\Country;

class TestModelForValidation extends Country
{
    use Validation;

    public bool $injectUniqueIdentifier = true;

    protected $rules = [
        'country_name' => ['required', 'custom'],
        'iso_code_2' => ['required', 'unique:,,,,,status,1'],
        'iso_code_3' => 'required:create',
        'format' => 'required:update',
    ];

    public function prepareCustomRule($params, $field)
    {
        return 'string';
    }
}
