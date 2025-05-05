<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Classes\HubManager;
use Igniter\System\Classes\LanguageManager;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Models\Extension;
use Igniter\System\Models\Language;
use Igniter\System\Models\Translation;
use Illuminate\Support\Facades\Http;
use stdClass;

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
        ->and($result[0])->toBeInstanceOf(stdClass::class)
        ->and($result[0]->code)->toBe('igniter')
        ->and($result[0]->name)->toBe('Application')
        ->and($result[0]->files)->toBe(['/path/to/lang/en/file.php']);
});

it('publishes translations for a language', function() {
    app()->instance(HubManager::class, $hubManager = mock(HubManager::class));
    $hubManager->shouldReceive('publishTranslations')->once()->with('fa', [
        'name' => 'tastyigniter',
        'type' => 'core',
        'ver' => Igniter::version(),
        'files' => [
            [
                'name' => 'default.php',
                'strings' => [
                    [
                        'key' => 'text_default',
                        'value' => 'Default',
                    ],
                ],
            ],
        ],
    ]);
    $hubManager->shouldReceive('publishTranslations')->once()->with('fa', [
        'name' => 'igniter.api',
        'type' => 'extension',
        'ver' => '1.0.0',
        'files' => [
            [
                'name' => 'default.php',
                'strings' => [
                    [
                        'key' => 'text_default',
                        'value' => 'Default',
                    ],
                ],
            ],
        ],
    ]);
    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
    $updateManager->shouldReceive('getInstalledItems')->once()->andReturn([
        [
            'name' => 'igniter.api',
            'type' => 'extension',
            'ver' => '1.0.0',
        ],
    ]);

    $language = Language::factory()->create(['code' => 'fa', 'status' => 1]);
    $language->translations()->saveMany([
        Translation::create(['locale' => 'fa', 'namespace' => 'igniter', 'group' => 'default', 'item' => 'text_default', 'text' => 'Default']),
        Translation::create(['locale' => 'fa', 'namespace' => 'igniter.api', 'group' => 'default', 'item' => 'text_default', 'text' => 'Default']),
    ]);
    $expectedResponse = ['message' => 'ok'];
    Http::fake(['https://api.tastyigniter.com/v2/language/upload' => Http::response($expectedResponse)]);

    resolve(LanguageManager::class)->publishTranslations($language);
});

it('applies language pack and returns data', function() {
    $expectedResponse = ['data' => ['result' => 'success']];
    Http::fake(['https://api.tastyigniter.com/v2/language/apply' => Http::response($expectedResponse)]);
    Extension::create(['name' => 'igniter.api', 'status' => 1]);
//    app()->instance(UpdateManager::class, $updateManager = mock(UpdateManager::class));
//    $updateManager->shouldReceive('getInstalledItems')->once()->andReturn([
//        [
//            'name' => 'igniter.api',
//            'type' => 'extension',
//            'ver' => '1.0.0',
//        ],
//    ]);

    $manager = resolve(LanguageManager::class);
    $result = $manager->applyLanguagePack('en');

    expect($result)->toBe($expectedResponse['data']);
});

it('returns language details by locale', function() {
    $expectedResponse = ['data' => ['name' => 'English']];
    $manager = resolve(LanguageManager::class);
    Http::fake(['https://api.tastyigniter.com/v2/language/en' => Http::response($expectedResponse)]);

    expect($manager->findLanguage('en'))->toBe($expectedResponse['data']);
});

it('installs language pack successfully', function() {
    $expectedResponse = [
        'data' => [
            'name' => 'test',
            'file' => 'default.php',
            'hash' => 'etag',
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/language/download' => Http::response($expectedResponse)]);
    File::shouldReceive('makeDirectory')->once();
    File::shouldReceive('put')->once();
    $manager = resolve(LanguageManager::class);

    $manager->installLanguagePack('en', ['name' => 'test', 'file' => 'default.php', 'hash' => 'etag']);
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
