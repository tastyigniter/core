<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Igniter\System\Classes\FormRequest;
use Illuminate\Validation\ValidationException;

it('throws validation exception on failed validation', function() {
    $formRequest = new class extends FormRequest
    {
        public function rules(): array
        {
            return [
                'name' => 'required',
            ];
        }
    };

    $formRequest->setContainer(app());

    expect(fn() => $formRequest->validateResolved())->toThrow(ValidationException::class);
});
