<?php

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\System\Classes\LanguageManager;
use Igniter\System\Models\Language;

it('loads languages index page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.languages'))
        ->assertOk();
});

it('searches languages successfully', function() {
    $language = Language::factory()->create();
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('searchLanguages')->with($language->name)->andReturn([
        [
            'name' => $language->name,
            'code' => $language->code,
            'icon' => [
                'url' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABKklEQVR42mNk',
                'class' => 'flag-icon flag-icon-gb',
                'style' => 'width: 16px; height: 11px;',
            ],
            'description' => 'description',
        ],
    ]);

    actingAsSuperUser()
        ->get(route('igniter.system.languages', ['slug' => 'search'])
            .'?'.http_build_query(['filter' => ['search' => $language->name]]))
        ->assertOk()
        ->assertSee($language->name);
});

it('returns empty array when search filter is invalid', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.languages', ['slug' => 'search'])
            .'?'.http_build_query(['filter' => []]))
        ->assertOk()
        ->assertJson([]);
});

it('loads languages create page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.languages', ['slug' => 'create']))
        ->assertOk();
});

it('loads languages edit page', function() {
    $language = Language::factory()->create();

    actingAsSuperUser()
        ->get(route('igniter.system.languages', ['slug' => 'edit/'.$language->getKey()]))
        ->assertOk();
});

it('sets default language successfully', function() {
    $language = Language::factory()->create(['status' => 1]);

    actingAsSuperUser()
        ->post(route('igniter.system.languages'), [
            'default' => $language->code,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSetDefault',
        ])
        ->assertOk();

    Language::clearDefaultModel();
    expect(Language::getDefault()->getKey())->toBe($language->getKey());
});

it('filters language translations successfully', function() {
    $language = Language::factory()->create();

    actingAsSuperUser()
        ->post(route('igniter.system.languages', ['slug' => 'edit/'.$language->getKey()]), [
            'Language' => [
                '_group' => 'igniter.user',
                '_search' => 'Text to search',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSubmitFilter',
        ])
        ->assertOk();
});

it('checks for language pack updates', function() {
    $language = Language::factory()->create();
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('applyLanguagePack')->once()->with($language->code, [])->andReturn([
        [
            'code' => $language->code,
            'name' => $language->name,
            'locale' => $language->code,
            'version' => '1.0.0',
            'description' => 'description',
            'icon' => [
                'url' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABKklEQVR42mNk',
                'class' => 'flag-icon flag-icon-gb',
                'style' => 'width: 16px; height: 11px;',
            ],
        ],
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.languages', ['slug' => 'edit/'.$language->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onCheckUpdates',
        ])
        ->assertOk();
});

it('publishes translated strings', function() {
    $language = Language::factory()->create();
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('publishTranslations')->once();

    actingAsSuperUser()
        ->post(route('igniter.system.languages', ['slug' => 'edit/'.$language->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onPublishTranslations',
        ])
        ->assertOk();
});

it('applies marketplace locale items', function() {
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('findLanguage')->once()->with('fr_FR')->andReturn([
        'code' => 'fr',
        'locale' => 'fr_FR',
        'name' => 'French',
        'version' => '1.0.0',
    ]);
    $languageManager->shouldReceive('applyLanguagePack')->once()->with('fr_FR')->andReturn($applyResponse = [
        'code' => 'fr',
        'locale' => 'fr_FR',
        'name' => 'French',
        'version' => '1.0.0',
        'description' => 'description',
        'icon' => [
            'url' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABKklEQVR42mNk',
            'class' => 'flag-icon flag-icon-gb',
            'style' => 'width: 16px; height: 11px;',
        ],
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.languages'), [
            'items' => [
                [
                    'name' => $applyResponse['locale'],
                    'type' => 'extension',
                    'ver' => '1.0.0',
                    'action' => 'update',
                ],
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyItems',
        ])
        ->assertOk()
        ->assertJson([
            'steps' => [
                'update-'.$applyResponse['code'] => [
                    'meta' => $applyResponse,
                    'process' => 'update-'.$applyResponse['code'],
                    'progress' => sprintf(lang('igniter::system.languages.alert_update_progress'),
                        $applyResponse['locale'], $applyResponse['name'],
                    ),
                ],
            ],
        ]);
});

it('flashes error when missing items to apply in request', function() {
    actingAsSuperUser()
        ->post(route('igniter.system.languages'), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyItems',
        ])
        ->assertStatus(406);
});

it('applies update for marketplace locale translated strings', function() {
    $language = Language::factory()->create();
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('applyLanguagePack')->once()->with($language->code, [])->andReturn([
        $applyResponse = [
            'code' => $language->code,
            'locale' => 'fr_FR',
            'name' => 'French',
            'version' => '1.0.0',
            'description' => 'description',
            'icon' => [
                'url' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAABKklEQVR42mNk',
                'class' => 'flag-icon flag-icon-gb',
                'style' => 'width: 16px; height: 11px;',
            ],
        ],
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.languages', ['slug' => 'edit/'.$language->getKey()]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onApplyUpdate',
        ])
        ->assertOk()
        ->assertJson([
            'steps' => [
                'update-'.$language->code => [
                    'meta' => $applyResponse,
                    'process' => 'update-'.$language->code,
                    'progress' => sprintf(lang('igniter::system.languages.alert_update_progress'),
                        $applyResponse['locale'], $applyResponse['name'],
                    ),
                ],
            ],
        ]);
});

it('processes update for marketplace locale translated strings', function() {
    $language = Language::factory()->create();
    $languageManager = mock(LanguageManager::class);
    app()->instance(LanguageManager::class, $languageManager);
    $languageManager->shouldReceive('installLanguagePack')->once()->with($language->code, [
        'name' => $language->code,
        'type' => 'extension',
        'ver' => '1.0.0',
        'build' => '383',
        'hash' => 'download-file-hash',
    ]);

    actingAsSuperUser()
        ->post(route('igniter.system.languages', ['slug' => 'edit/'.$language->getKey()]), [
            'process' => 'update-'.$language->code,
            'meta' => [
                'code' => $language->code,
                'name' => $language->code,
                'author' => 'Author',
                'type' => 'extension',
                'version' => '1.0.0+383',
                'hash' => 'download-file-hash',
                'description' => 'description',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onProcessItems',
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => sprintf(lang('igniter::system.languages.alert_update_complete'), $language->code, $language->code),
        ]);
});



