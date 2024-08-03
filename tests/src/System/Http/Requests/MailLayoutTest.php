<?php

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\MailLayoutRequest;
use Illuminate\Validation\Rule;

it('has required rule for inputs: name and code', function() {
    $rules = (new MailLayoutRequest)->rules();

    expect('required')->toBeIn(array_get($rules, 'name'))
        ->and('required')->toBeIn(array_get($rules, 'code'));
});

it('has regex rule for code input', function() {
    expect('regex:/^[a-z-_\.\:]+$/i')->toBeIn(array_get((new MailLayoutRequest)->rules(), 'code'));
});

it('has sometimes rule for code input', function() {
    expect('sometimes')->toBeIn(array_get((new MailLayoutRequest)->rules(), 'code'));
});

it('has characters length between 2 and 32 characters rule for code input', function() {
    expect('between:2,32')->toBeIn(array_get((new MailLayoutRequest)->rules(), 'name'));
});

it('has unique rule for code input', function() {
    expect((string)(Rule::unique('mail_layouts')->ignore(null, 'layout_id')))
        ->toBeIn(
            collect(array_get((new MailLayoutRequest)->rules(), 'code'))->map(function($rule) {
                return (string)$rule;
            })->toArray()
        );
});

it('has string rule for inputs: layout_css, name, plain_layout and layout', function() {
    $rules = (new MailLayoutRequest)->rules();

    expect('string')->toBeIn(array_get($rules, 'layout'))
        ->and('string')->toBeIn(array_get($rules, 'layout_css'))
        ->and('string')->toBeIn(array_get($rules, 'name'))
        ->and('string')->toBeIn(array_get($rules, 'plain_layout'));
});

it('has nullable rule for input rules: layout_css and plain_layout', function() {
    $rules = (new MailLayoutRequest)->rules();

    expect('nullable')->toBeIn(array_get($rules, 'layout_css'))
        ->and('nullable')->toBeIn(array_get($rules, 'plain_layout'));
});
