<?php

namespace Tests\System\Requests;

use Igniter\System\Requests\LanguageRequest;

it('has required rule for inputs', function() {
    $rules = (new LanguageRequest)->rules();

    expect('required')->toBeIn(array_get($rules, 'name'))
        ->and('required')->toBeIn(array_get($rules, 'code'))
        ->and('required')->toBeIn(array_get($rules, 'status'));
});

it('has unique rule for code input', function() {
    expect('unique:languages')->toBeIn(array_get((new LanguageRequest)->rules(), 'code'));
})->skip();

it('has max characters rule for code input', function() {
    expect('between:2,32')->toBeIn(array_get((new LanguageRequest)->rules(), 'name'))
        ->and('max:2500')->toBeIn(array_get((new LanguageRequest)->rules(), 'translations.*.source'))
        ->and('max:2500')->toBeIn(array_get((new LanguageRequest)->rules(), 'translations.*.translation'));
});
