<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Models;

use Carbon\Carbon;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Classes\ThemeManager;
use Igniter\System\Models\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

it('returns all menu settings item', function() {
    (new Settings)->registerSettingItems('core', [
        'settings' => [],
    ]);

    $result = Settings::listMenuSettingItems(null, null, null);

    expect($result)->toBeArray();
});

it('returns date format options', function() {
    $result = Settings::getDateFormatOptions();

    expect($result)->toHaveKey('d M Y')
        ->and($result['d M Y'])->toBe(Carbon::now()->format('d M Y'));
});

it('returns time format options', function() {
    $result = Settings::getTimeFormatOptions();

    expect($result)->toHaveKey('h:i A', Carbon::now()->format('h:i A'));
});

it('returns page limit options', function() {
    $result = Settings::getPageLimitOptions();

    expect($result)->toHaveKey('10', '10');
});

it('returns menus page options when theme is active', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('getActiveThemeCode')->andReturn('test_theme');

    $result = Settings::getMenusPageOptions();

    expect($result)->toBeArray();
});

it('returns empty menus page options when no active theme', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('getActiveThemeCode')->andReturnNull();

    $result = Settings::getMenusPageOptions();

    expect($result)->toBeEmpty();
});

it('returns reservation page options when theme is active', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('getActiveThemeCode')->andReturn('test_theme');

    $result = Settings::getReservationPageOptions();

    expect($result)->toBeArray();
});

it('returns empty reservation page options when no active theme', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('getActiveThemeCode')->andReturnNull();

    $result = Settings::getReservationPageOptions();

    expect($result)->toBeEmpty();
});

it('checks if settings onboarding is complete', function() {
    Session::shouldReceive('has')->with('settings.errors')->andReturn(true);
    Session::shouldReceive('get')->with('settings.errors')->andReturn([]);

    $result = Settings::onboardingIsComplete();

    expect($result)->toBeTrue();
});

it('checks if settings onboarding is not complete', function() {
    Session::shouldReceive('has')->with('settings.errors')->andReturn(false);

    $result = Settings::onboardingIsComplete();

    expect($result)->toBeFalse();
});

it('checks if test mail onboarding is complete', function() {
    Settings::setPref('test_mail_sent', true);

    $result = Settings::onboardingMailIsComplete();

    expect($result)->toBeTrue();
});

it('checks if test mail onboarding is not complete', function() {
    $result = Settings::onboardingMailIsComplete();

    expect($result)->toBeFalse();
});

it('gets value attribute as json decoded', function() {
    $settings = new Settings(['value' => json_encode(['key' => 'value'])]);

    $result = $settings->value;

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('key', 'value');
});

it('gets value attribute as original', function() {
    $settings = new Settings(['value' => 'original_value']);

    $result = $settings->value;

    expect($result)->toBe('original_value');
});

it('gets null value attribute as null', function() {
    $settings = new Settings(['value' => null]);

    expect($settings->value)->toBeNull();
});

it('does not unserialize php serialized value attribute', function() {
    $payload = 'O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}';
    $settings = new Settings(['value' => $payload]);

    expect($settings->value)->toBe($payload);
});

it('encodes values as json', function($value, $expected) {
    expect(Settings::encodeValue($value))->toBe($expected);
})->with([
    'null' => [null, null],
    'string' => ['value', '"value"'],
    'serialized looking string' => ['a:1:{i:0;s:1:"x";}', '"a:1:{i:0;s:1:\"x\";}"'],
    'true' => [true, '"1"'],
    'false' => [false, '"0"'],
    'integer' => [10, '"10"'],
    'float' => [1.5, '"1.5"'],
    'array' => [['key' => 'value', 'url' => 'http://example.com/é'], '{"key":"value","url":"http://example.com/é"}'],
]);

it('stores values as json and returns the same value', function() {
    $payload = 'a:1:{i:0;O:8:"stdClass":0:{}}';
    Settings::set([
        'string_key' => $payload,
        'array_key' => ['one', 'two'],
        'bool_key' => true,
    ]);

    expect(DB::table('settings')->where('item', 'string_key')->value('value'))->toBe(json_encode($payload))
        ->and(Settings::get('string_key'))->toBe($payload)
        ->and(Settings::get('array_key'))->toBe(['one', 'two'])
        ->and(Settings::get('bool_key'))->toBe('1');
});

it('sets and gets configuration value', function() {
    Settings::set('test_key', 'test_value');

    $result = Settings::get('test_key');

    expect($result)->toBe('test_value');
});

it('sets and gets preference value', function() {
    Settings::setPref('pref_key', 'pref_value');

    $result = Settings::getPref('pref_key');

    expect($result)->toBe('pref_value');
});

it('returns null for field values when database is not configured', function() {
    Igniter::shouldReceive('hasDatabase')->andReturnFalse();
    $result = (new Settings)->getFieldValues();

    expect($result)->toBe([]);
});

it('removes core setting item', function() {
    $settings = new Settings;
    $settings->loadSettingItems();
    $settings->removeSettingItem('core.general');

    expect($settings->getSettingItem('core.general'))->toBeNull();
});

it('removes extension setting item', function() {
    $settings = new Settings;
    $settings->loadSettingItems();
    $settings->removeSettingItem('igniter.payregister.settings');

    expect($settings->getSettingItem('igniter.payregister.settings'))->toBeNull();
});

it('returns list of timezones', function() {
    $result = Settings::listTimezones();

    expect($result)->toHaveKey('UTC', 'UTC (UTC -00:00)');
});

it('returns default extensions', function() {
    $result = Settings::defaultExtensions();

    expect($result)->toContain('jpg')
        ->and($result)->toContain('mp4');
});

it('returns image extensions', function() {
    $result = Settings::imageExtensions();

    expect($result)->toContain('jpg')
        ->and($result)->toContain('png');
});

it('returns video extensions', function() {
    $result = Settings::videoExtensions();

    expect($result)->toContain('mp4')
        ->and($result)->toContain('avi');
});

it('returns audio extensions', function() {
    $result = Settings::audioExtensions();

    expect($result)->toContain('mp3')
        ->and($result)->toContain('wav');
});

it('configures settings model correctly', function() {
    $settings = new Settings;

    expect($settings->getTable())->toEqual('settings')
        ->and($settings->getKeyName())->toEqual('setting_id');
});
