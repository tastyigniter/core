<?php

namespace Igniter\Tests\System\Classes;

use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\LanguageManager;
use Igniter\System\Models\Extension;
use Igniter\System\Models\Language;
use Igniter\System\Models\Translation;
use Illuminate\Support\Facades\Http;

it('lists all loaded namespaces', function() {
    $manager = resolve(LanguageManager::class);
    $result = $manager->namespaces();

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['igniter', 'igniter.api']);
});

it('lists enabled languages', function() {
    Language::factory()->create(['code' => 'fa', 'status' => 1]);

    $manager = resolve(LanguageManager::class);
    $result = $manager->listLanguages();

    expect($result->isNotEmpty())->toBeTrue();
});

it('returns empty paths if language directory does not exist', function() {
    File::shouldReceive('exists')->with(base_path('language'))->andReturn(false);

    $manager = resolve(LanguageManager::class);

    expect($manager->paths())->toBe([]);
});

it('returns paths of language directories', function() {
    File::shouldReceive('exists')->with(base_path('language'))->andReturn(true);
    File::shouldReceive('directories')->with(base_path('language'))->andReturn(['/path/to/lang/en', '/path/to/lang/fr']);

    $manager = resolve(LanguageManager::class);
    $result = $manager->paths();

    expect($result)->toBe(['en' => '/path/to/lang/en', 'fr' => '/path/to/lang/fr'])
        ->and($result)->toBe($manager->paths());
});

it('lists locale packages for a given locale', function() {
    File::shouldReceive('glob')->andReturn(['/path/to/lang/en/file.php']);

    $manager = resolve(LanguageManager::class);
    $result = $manager->listLocalePackages('en');

    expect($result)->toBeArray()
        ->and($result[0])->toBeInstanceOf(\stdClass::class)
        ->and($result[0]->code)->toBe('igniter')
        ->and($result[0]->name)->toBe('Application')
        ->and($result[0]->files)->toBe(['/path/to/lang/en/file.php']);
});

it('publishes translations for a language', function() {
    $language = Language::factory()->create(['code' => 'fa', 'status' => 1]);
    $language->translations()->saveMany([
        Translation::create(['locale' => 'fa', 'group' => 'igniter', 'item' => 'default', 'text' => 'Default']),
    ]);
    $expectedResponse = ['publish' => 'success'];
    Http::fake(['https://api.tastyigniter.com/v2/language/upload' => Http::response($expectedResponse)]);

    $manager = resolve(LanguageManager::class);
    expect($manager->publishTranslations($language))->toBe($expectedResponse);
});

it('throws exception if language not found when requesting update list', function() {
    $manager = resolve(LanguageManager::class);

    expect(fn() => $manager->requestUpdateList('invalid'))->toThrow(ApplicationException::class, 'Language not found');
});

it('returns update list correctly', function() {
    $expectedResponse = ['data' => ['result' => 'success']];
    Http::fake(['https://api.tastyigniter.com/v2/language/apply' => Http::response($expectedResponse)]);
    $language = Language::factory()->create(['code' => 'en', 'status' => 1]);

    $manager = resolve(LanguageManager::class);
    $result = $manager->requestUpdateList($language->code);

    expect($result['items'])->toBe($expectedResponse['data']);
});

it('applies language pack and returns data', function() {
    $expectedResponse = ['data' => ['result' => 'success']];
    Http::fake(['https://api.tastyigniter.com/v2/language/apply' => Http::response($expectedResponse)]);
    Extension::create(['name' => 'Igniter.Api', 'status' => 1]);

    $manager = resolve(LanguageManager::class);
    $result = $manager->applyLanguagePack('en');

    expect($result)->toBe($expectedResponse['data']);
});

it('returns languages matching search term', function() {
    $expectedResponse = ['data' => [['name' => 'English']]];
    Http::fake(['https://api.tastyigniter.com/v2/languages' => Http::response($expectedResponse)]);
    $manager = resolve(LanguageManager::class);

    $expectedResponse['data'][0]['require'] = [];
    expect($manager->searchLanguages('term'))->toBe($expectedResponse);
});

it('returns language details by locale', function() {
    $expectedResponse = ['data' => ['name' => 'English']];
    $manager = resolve(LanguageManager::class);
    Http::fake(['https://api.tastyigniter.com/v2/language/en' => Http::response($expectedResponse)]);

    expect($manager->findLanguage('en'))->toBe($expectedResponse['data']);
});

it('installs language pack successfully', function() {
    $expectedResponse = ['filename.php' => [
        'text_key' => 'This is a text',
    ]];
    Http::fake(['https://api.tastyigniter.com/v2/language/download' => Http::response($expectedResponse, 200, [
        'TI-ETag' => 'etag',
    ])]);
    File::shouldReceive('makeDirectory')->once();
    File::shouldReceive('put')->once();
    $manager = resolve(LanguageManager::class);

    expect($manager->installLanguagePack('en', ['name' => 'test', 'hash' => 'etag']))->toBeTrue();
});

it('lists translations for a given package', function() {
    $manager = resolve(LanguageManager::class);
    $language = Language::factory()->create(['code' => 'en', 'status' => 1]);

    $result = $manager->listTranslations($language, 'igniter.api');
    expect($result->strings->isNotEmpty())->toBeTrue();
});

it('lists translations with filter applied', function() {
    $manager = resolve(LanguageManager::class);
    $language = Language::factory()->create(['code' => 'en', 'status' => 1]);

    $result = $manager->listTranslations($language, 'igniter.api', 'unchanged');
    expect($result->strings->isEmpty())->toBeTrue();

    $result = $manager->listTranslations($language, 'igniter.api', 'changed', 'actions.text_index');
    expect($result->strings->isNotEmpty())->toBeTrue();
});

it('lists translations with search term applied', function() {
    $manager = resolve(LanguageManager::class);
    $language = Language::factory()->create(['code' => 'en', 'status' => 1]);

    $result = $manager->listTranslations($language, 'igniter', null, 'invalid-term');
    expect($result->strings->isEmpty())->toBeTrue();
});

it('returns empty translations if no packages found', function() {
    $manager = resolve(LanguageManager::class);
    $language = Language::factory()->create(['code' => 'en', 'status' => 1]);

    $result = $manager->listTranslations($language, 'invalid-code', null, 'value1');
    expect($result->strings->isEmpty())->toBeTrue();
});
