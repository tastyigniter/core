<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Requests;

use Igniter\System\Http\Requests\LanguageRequest;

it('returns correct attribute labels', function() {
    $attributes = (new LanguageRequest)->attributes();

    expect($attributes)->toHaveKey('name', lang('igniter::admin.label_name'))
        ->and($attributes)->toHaveKey('code', lang('igniter::system.languages.label_code'))
        ->and($attributes)->toHaveKey('status', lang('igniter::admin.label_status'))
        ->and($attributes)->toHaveKey('translations.*.source', lang('igniter::system.column_source'))
        ->and($attributes)->toHaveKey('translations.*.translation', lang('igniter::system.column_translation'));
});

it('returns correct validation rules', function() {
    $rules = (new LanguageRequest)->rules();

    expect($rules['name'])->toContain('required', 'string', 'between:2,32')
        ->and($rules['code'])->toContain('required', 'regex:/^[a-zA-Z_]+$/')
        ->and($rules['code'][2]->__toString())->toBe('unique:languages,NULL,NULL,language_id')
        ->and($rules['status'])->toContain('required', 'boolean')
        ->and($rules['translations.*.source'])->toContain('string', 'max:2500')
        ->and($rules['translations.*.translation'])->toContain('nullable', 'string', 'max:2500');
});
