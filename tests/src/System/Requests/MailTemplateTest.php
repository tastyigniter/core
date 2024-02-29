<?php

namespace Tests\System\Requests;

use Igniter\System\Requests\MailTemplateRequest;
use Illuminate\Validation\Rule;

it('has required rule for inputs: label, subject and code', function () {
    $rules = (new MailTemplateRequest)->rules();

    expect('required')->toBeIn(array_get($rules, 'label'))
        ->and('required')->toBeIn(array_get($rules, 'subject'))
        ->and('required')->toBeIn(array_get($rules, 'code'));
});

it('has string rule for input rules: label, subject and plain_body', function () {
    $rules = (new MailTemplateRequest)->rules();

    expect('string')->toBeIn(array_get($rules, 'label'))
        ->and('string')->toBeIn(array_get($rules, 'subject'))
        ->and('string')->toBeIn(array_get($rules, 'plain_body'));
});

it('has nullable for plain_body input', function () {
    expect('nullable')->toBeIn(array_get((new MailTemplateRequest())->rules(), 'plain_body'));
});

it('has layout_id for integer input', function () {
    expect('integer')->toBeIn(array_get((new MailTemplateRequest())->rules(), 'layout_id'));
});

it('has regex rule for code input', function () {
    expect('regex:/^[a-z-_\.\:]+$/i')->toBeIn(array_get((new MailTemplateRequest())->rules(), 'code'));
});

it('has max of 255 characters rule for code input', function () {
    expect('max:255')->toBeIn(array_get((new MailTemplateRequest)->rules(), 'code'));
});

it('has min of 2 characters rule for code input', function () {
    expect('max:255')->toBeIn(array_get((new MailTemplateRequest)->rules(), 'code'));
});

it('has sometimes rule for code input', function () {
    expect('sometimes')->toBeIn(array_get((new MailTemplateRequest)->rules(), 'code'));
});

it('has unique rule for code input', function () {
    expect((string)(Rule::unique('mail_templates', 'code')->ignore(null, 'template_id')))
        ->toBeIn(
            collect(array_get((new MailTemplateRequest)->rules(), 'code'))->map(function ($rule) {
                return (string)$rule;
            })->toArray()
        );
});
