<?php

namespace Igniter\Tests\System\Models;

use Igniter\Flame\Support\Facades\File;
use Igniter\System\Actions\SettingsModel;
use Igniter\System\Models\MailTheme;
use Illuminate\Support\Facades\Cache;

it('initializes settings data with default values', function() {
    $mailTheme = new MailTheme;
    $mailTheme->initSettingsData();

    expect($mailTheme->body_bg)->toBe(MailTheme::BODY_BG)
        ->and($mailTheme->content_bg)->toBe(MailTheme::WHITE_COLOR);
});

it('resets cache after saving', function() {
    Cache::put((new MailTheme)->cacheKey, 'cached_data');

    MailTheme::flushEventListeners();
    $mailTheme = MailTheme::create();

    expect(Cache::get($mailTheme->cacheKey))->toBeNull();
});

it('renders CSS from cache if available', function() {
    $cacheKey = (new MailTheme)->cacheKey;
    Cache::put($cacheKey, 'cached_data');

    $result = MailTheme::renderCss();

    expect($result)->toBe('cached_data');
});

it('compiles and caches CSS if not available in cache', function() {
    File::shouldReceive('symbolizePath')->with('igniter::views/system/_mail/themes/default.css')->andReturn('file_path');
    File::shouldReceive('get')->with('file_path')->andReturn('compiled_css');

    $result = MailTheme::renderCss();

    expect($result)->toBe('compiled_css');
});

it('throws exception when rendering css', function() {
    File::shouldReceive('symbolizePath')->with('igniter::views/system/_mail/themes/default.css')->andReturn('file_path');
    File::shouldReceive('get')->with('file_path')->andThrow(new \Exception('Error compiling CSS'));

    $result = MailTheme::renderCss();

    expect($result)->toBe('/* Error compiling CSS */');
});

it('compiles CSS from file', function() {
    File::shouldReceive('symbolizePath')->with('igniter::views/system/_mail/themes/default.css')->andReturn('file_path');
    File::shouldReceive('get')->with('file_path')->andReturn('file_css');

    $result = MailTheme::compileCss();

    expect($result)->toBe('file_css');
});

it('makes CSS variable correctly', function() {
    $mailTheme = new class extends MailTheme
    {
        public static function testMakeCssVars()
        {
            return self::makeCssVars();
        }
    };

    $result = $mailTheme::testMakeCssVars();

    expect($result)->toHaveKey('body-bg', MailTheme::BODY_BG);
});

it('configures mail theme model correctly', function() {
    $mailTheme = new MailTheme;

    expect($mailTheme->implement)->toContain(SettingsModel::class)
        ->and($mailTheme->settingsCode)->toBe('system_mail_theme_settings')
        ->and($mailTheme->settingsFieldsConfig)->toBe('mail_themes')
        ->and($mailTheme->cacheKey)->toBe('system::mailtheme.custom_css');
});
