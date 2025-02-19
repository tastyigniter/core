<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Models;

use Igniter\Flame\Database\Traits\Purgeable;
use Igniter\Flame\Translation\FileLoader;
use Igniter\System\Classes\LanguageManager;
use Igniter\System\Models\Concerns\Defaultable;
use Igniter\System\Models\Concerns\Switchable;
use Igniter\System\Models\Language;
use Igniter\System\Models\Translation;
use Illuminate\Support\Facades\Lang;

it('returns null when finding language by null code', function() {
    expect(Language::findByCode())->toBeNull();
});

it('returns language when finding by valid code', function() {
    Language::create(['code' => 'en', 'name' => 'English']);
    $result = Language::findByCode('en');

    expect($result->code)->toBe('en')
        ->and($result->name)->toBe('English');
});

it('returns active locale', function() {
    Language::factory()->create(['code' => 'en', 'status' => 1]);

    $result = Language::getActiveLocale();

    expect($result->getKey())->toBe(Language::getActiveLocale()->getKey());
});

it('returns supported languages list', function() {
    Language::create(['code' => 'en', 'name' => 'English', 'status' => 1]);
    Language::create(['code' => 'fr', 'name' => 'French', 'status' => 1]);

    $result = Language::listSupported();

    expect($result)->toHaveCount(2)
        ->and($result)->toHaveKey('en')
        ->and($result)->toHaveKey('fr');
});

it('returns true when more than one supported language', function() {
    Language::create(['code' => 'en', 'name' => 'English', 'status' => 1]);
    Language::create(['code' => 'fr', 'name' => 'French', 'status' => 1]);

    $result = Language::supportsLocale();

    expect($result)->toBeTrue();
});

it('adds translations successfully', function() {
    $language = Language::create(['code' => 'en']);
    $result = $language->addTranslations(['en::group.key' => ['translation' => 'value']]);

    expect($result)->toBeTrue();
});

it('skips invalid translation keys', function() {
    $language = Language::create(['code' => 'en']);
    $result = $language->addTranslations(['invalid_key' => ['translation' => 'value']]);

    expect($result)->toBeTrue();
});

it('returns group options for a given locale', function() {
    $language = new Language;
    $localePackage = (object)['code' => 'en', 'name' => 'English'];
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('listLocalePackages')->with('en')->andReturn([$localePackage]);

    $result = $language->getGroupOptions('en');

    expect($result)->toHaveKey('en', 'English');
});

it('returns lines for a given locale, group, and namespace', function() {
    $language = new Language;
    $lines = ['key' => 'value'];
    $loader = mock(FileLoader::class);
    app()->instance('translation.loader', $loader);
    $loader->shouldReceive('load')->with('en', 'group', 'namespace')->andReturn($lines);

    $result = $language->getLines('en', 'group', 'namespace');

    expect($result)->toHaveKey('key')
        ->and($result['key'])->toBe('value');
});

it('returns empty lines when no translations found', function() {
    $language = new Language(['code' => 'en']);
    $loader = mock(FileLoader::class);
    app()->instance('translation.loader', $loader);
    $loader->shouldReceive('load')->with('en', 'group', 'namespace')->andReturn([]);

    $result = $language->getTranslations('group', 'namespace');

    expect($result)->toBeEmpty();
});

it('updates translations successfully', function() {
    $language = Language::create(['code' => 'en']);
    $result = $language->updateTranslations('group', 'namespace', ['key' => 'new value']);

    expect($result)->toHaveKey('key')
        ->and($result['key'])->toBe('new value');
});

it('does not update translation if text is the same', function() {
    $language = Language::create(['code' => 'en']);
    Lang::shouldReceive('get')->andReturn('same value');

    expect($language->updateTranslation('group', 'namespace', 'key', 'same value'))->toBeFalse();
});

it('updates translation if text is different', function() {
    $language = Language::create(['code' => 'en']);
    Lang::shouldReceive('get')->andReturn('old value');

    expect($language->updateTranslation('group', 'namespace', 'key', 'new value'))->toBeTrue();
});

it('deletes related translations on language delete', function() {
    $language = Language::create(['code' => 'en']);
    $language->translations()->saveMany([
        new Translation(['locale' => 'en', 'code' => 'group', 'namespace' => 'namespace', 'item' => 'key', 'text' => 'value']),
        new Translation(['locale' => 'en', 'code' => 'group', 'namespace' => 'namespace', 'item' => 'key2', 'text' => 'value2']),
    ]);

    $language->delete();

    expect(Translation::where('locale', 'en')->count())->toBe(0);
});

it('configures language model correctly', function() {
    $language = new Language;

    expect(class_uses_recursive($language))
        ->toContain(Defaultable::class)
        ->toContain(Purgeable::class)
        ->toContain(Switchable::class)
        ->and($language->getTable())->toBe('languages')
        ->and($language->getKeyName())->toBe('language_id')
        ->and($language->getCasts())->toEqual([
            'language_id' => 'int',
            'original_id' => 'integer',
            'version' => 'array',
            'is_default' => 'boolean',
            'status' => 'boolean',
        ])
        ->and($language->relation['hasMany'])->toEqual([
            'translations' => [Translation::class, 'foreignKey' => 'locale', 'otherKey' => 'code', 'delete' => true],
        ])
        ->and($language->timestamps)->toBeTrue()
        ->and($language->defaultableKeyName())->toBe('code');
});
