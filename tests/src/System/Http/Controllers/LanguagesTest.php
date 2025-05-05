<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Http\Controllers;

use Igniter\System\Classes\LanguageManager;
use Igniter\System\Models\Language;

it('loads languages index page', function() {
    actingAsSuperUser()
        ->get(route('igniter.system.languages'))
        ->assertOk();
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

    Language::clearDefaultModels();
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
        [
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
            'message' => $language->code.': Translated strings for addon (French) have been updated.',
            'success' => true,
            'redirect' => admin_url('languages/edit/'.$language->getKey()),
        ]);
});
