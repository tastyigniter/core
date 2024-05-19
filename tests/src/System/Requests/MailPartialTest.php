<?php

namespace Tests\System\Requests;

use Igniter\System\Requests\MailPartialRequest;
use Illuminate\Validation\Rule;

it('has required rule for inputs: name, code and html', function() {
    $rules = (new MailPartialRequest)->rules();

    expect('required')->toBeIn(array_get($rules, 'name'))
        ->and('required')->toBeIn(array_get($rules, 'code'))
        ->and('required')->toBeIn(array_get($rules, 'html'));
});

it('has regex rule for code input', function() {
    expect('regex:/^[a-z-_\.\:]+$/i')->toBeIn(array_get((new MailPartialRequest())->rules(), 'code'));
});

it('has sometimes rule for code input', function() {
    expect('sometimes')->toBeIn(array_get((new MailPartialRequest())->rules(), 'code'));
});

it('has unique rule for code input', function() {
    expect((string)(Rule::unique('mail_partials')->ignore(null, 'partial_id')))
        ->toBeIn(
            collect(array_get((new MailPartialRequest)->rules(), 'code'))->map(function($rule) {
                return (string)$rule;
            })->toArray()
        );
});

it('has string rule for input rules: html, name, and text', function() {
    $rules = (new MailPartialRequest)->rules();

    expect('string')->toBeIn(array_get($rules, 'text'))
        ->and('string')->toBeIn(array_get($rules, 'name'))
        ->and('string')->toBeIn(array_get($rules, 'html'));
});

it('has nullable rule for text input', function() {
    $rules = (new MailPartialRequest)->rules();

    expect('nullable')->toBeIn(array_get($rules, 'text'));
});
