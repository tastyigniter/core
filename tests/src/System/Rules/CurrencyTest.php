<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Rules;

use Igniter\System\Models\Currency;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

beforeEach(function() {
    Currency::clearDefaultModels();
});

function currencyFormattedNumbersValidator(array $data): Validator
{
    return validator($data, [
        'price' => ['required', 'currency', 'min:0'],
        'special.amount' => ['nullable', 'currency'],
        'name' => ['required', 'string'],
    ]);
}

it('accepts numeric input formatted with the default currency separators', function() {
    Currency::factory()->create([
        'decimal_sign' => ',',
        'thousand_sign' => '.',
    ])->makeDefault();
    Currency::clearDefaultModels();

    $validator = currencyFormattedNumbersValidator([
        'price' => '11,50',
        'special' => ['amount' => '1.234,56'],
        'name' => 'a, b',
    ]);

    expect($validator->passes())->toBeTrue()
        ->and($validator->validated()['price'])->toBe('11.50')
        ->and($validator->validated()['special']['amount'])->toBe('1234.56')
        ->and($validator->validated()['name'])->toBe('a, b');
});

it('leaves machine-format numeric input untouched under a comma-decimal currency', function() {
    Currency::factory()->create([
        'decimal_sign' => ',',
        'thousand_sign' => '.',
    ])->makeDefault();
    Currency::clearDefaultModels();

    $validator = currencyFormattedNumbersValidator([
        'price' => '11.50',
        'name' => 'a',
    ]);

    expect($validator->passes())->toBeTrue()
        ->and($validator->validated()['price'])->toBe('11.50');
});

it('keeps rejecting comma decimals when the default currency uses a dot decimal sign', function() {
    Currency::factory()->create([
        'decimal_sign' => '.',
        'thousand_sign' => ',',
    ])->makeDefault();
    Currency::clearDefaultModels();

    $validator = currencyFormattedNumbersValidator([
        'price' => '11,50',
        'name' => 'a',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and(fn() => $validator->validate())->toThrow(ValidationException::class);
});

it('accepts integer and float values', function() {
    $validator = validator(
        ['price' => 11, 'amount' => 11.5],
        ['price' => ['currency'], 'amount' => ['currency']],
    );

    expect($validator->passes())->toBeTrue();
});

it('rejects non-numeric strings', function() {
    $validator = validator(
        ['price' => 'free'],
        ['price' => ['currency']],
    );

    expect($validator->fails())->toBeTrue();
});
