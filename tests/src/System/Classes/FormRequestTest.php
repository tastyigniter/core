<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Igniter\System\Classes\FormRequest;
use Igniter\System\Models\Currency;
use Illuminate\Validation\ValidationException;

it('throws validation exception on failed validation', function() {
    $formRequest = new class extends FormRequest
    {
        public function rules(): array
        {
            return [
                ['title' => 'required'],
                'name' => 'required',
            ];
        }
    };

    $formRequest->setContainer(app());

    expect(fn() => $formRequest->validateResolved())->toThrow(ValidationException::class);
});

function currencyFormattedNumbersFormRequest(): FormRequest
{
    $formRequest = new class extends FormRequest
    {
        public function rules(): array
        {
            return [
                'price' => ['required', 'numeric', 'min:0'],
                'special.amount' => ['nullable', 'numeric'],
                'name' => ['required', 'string'],
            ];
        }
    };

    $formRequest->setContainer(app());

    return $formRequest;
}

it('accepts numeric input formatted with the default currency separators', function() {
    Currency::factory()->create([
        'decimal_sign' => ',',
        'thousand_sign' => '.',
    ])->makeDefault();
    Currency::$defaultModels = [];

    $formRequest = currencyFormattedNumbersFormRequest();
    $formRequest->merge([
        'price' => '11,50',
        'special' => ['amount' => '1.234,56'],
        'name' => 'a, b',
    ]);

    $formRequest->validateResolved();

    expect($formRequest->validated()['price'])->toBe('11.50')
        ->and($formRequest->validated()['special']['amount'])->toBe('1234.56')
        ->and($formRequest->validated()['name'])->toBe('a, b');
});

it('leaves machine-format numeric input untouched under a comma-decimal currency', function() {
    Currency::factory()->create([
        'decimal_sign' => ',',
        'thousand_sign' => '.',
    ])->makeDefault();
    Currency::$defaultModels = [];

    $formRequest = currencyFormattedNumbersFormRequest();
    $formRequest->merge(['price' => '11.50', 'name' => 'a']);

    $formRequest->validateResolved();

    expect($formRequest->validated()['price'])->toBe('11.50');
});

it('keeps rejecting comma decimals when the default currency uses a dot decimal sign', function() {
    Currency::factory()->create([
        'decimal_sign' => '.',
        'thousand_sign' => ',',
    ])->makeDefault();
    Currency::$defaultModels = [];

    $formRequest = currencyFormattedNumbersFormRequest();
    $formRequest->merge(['price' => '11,50', 'name' => 'a']);

    expect(fn() => $formRequest->validateResolved())->toThrow(ValidationException::class);
});
