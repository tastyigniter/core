<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Helpers;

use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Helpers\SystemHelper;

it('returns correct PHP version', function() {
    $version = (new SystemHelper)->phpVersion();

    expect($version)->toBe(PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION);
});

it('returns correct extension version', function() {
    $version = (new SystemHelper)->extensionVersion('json');

    expect($version)->toBe(phpversion('json'));
});

it('normalizes version correctly', function() {
    $version = (new SystemHelper)->normalizeVersion('7.4.3-1+ubuntu18.04.1+deb.sury.org+1');

    expect($version)->toBe('7.4.3');
});

it('validates version correctly', function() {
    expect((new SystemHelper)->validateVersion('v1.2.3'))->toBeTrue()
        ->and((new SystemHelper)->validateVersion('1.2.3'))->toBeTrue()
        ->and((new SystemHelper)->validateVersion('1.2'))->toBeTrue()
        ->and((new SystemHelper)->validateVersion('1'))->toBeTrue()
        ->and((new SystemHelper)->validateVersion('1.2.3-alpha'))->toBeTrue()
        ->and((new SystemHelper)->validateVersion('invalid-version'))->toBeFalse();
});

it('asserts ini_set works', function() {
    $result = (new SystemHelper)->assertIniSet();

    expect($result)->toBeTrue();
});

it('asserts ini max execution time is less than 120', function() {
    $oldValue = ini_get('max_execution_time');
    ini_set('max_execution_time', 100);

    $result = (new SystemHelper)->assertIniMaxExecutionTime(120);

    expect($result)->toBeTrue();
    ini_set('max_execution_time', $oldValue);
});

it('asserts ini memory limit is less than 256MB', function() {
    expect((new SystemHelper)->assertIniMemoryLimit(250))->toBeBool();
});

it('retrieves PHP ini value as bool', function() {
    $oldValue = ini_get('display_errors');
    ini_set('display_errors', 1);

    $result = (new SystemHelper)->phpIniValueAsBool('display_errors');

    expect($result)->toBeTrue();
    ini_set('display_errors', $oldValue);
});

it('retrieves PHP ini value in bytes', function() {
    $result = (new SystemHelper)->phpIniValueInBytes('memory_limit');

    expect($result)->not()->toBeNull();
});

it('normalizes PHP size in bytes', function() {
    expect((new SystemHelper)->phpSizeInBytes('2G'))->toBe(2 * 1024 * 1024 * 1024)
        ->and((new SystemHelper)->phpSizeInBytes('2M'))->toBe(2 * 1024 * 1024)
        ->and((new SystemHelper)->phpSizeInBytes('2K'))->toBe(2 * 1024);
});

it('replaces value in env file', function() {
    File::shouldReceive('put')->once();
    File::shouldReceive('get')->andReturn('APP_ENV=local');

    (new SystemHelper)->replaceInEnv('APP_ENV', 'APP_ENV=production');
});

it('throws exception for unsupported extension config file', function() {
    File::shouldReceive('exists')->with('/path/extension.json')->andReturn(true);

    expect(fn() => (new SystemHelper)->extensionConfigFromFile('/path'))->toThrow(SystemException::class);
});

it('validates extension config correctly', function() {
    $config = [
        'code' => 'test.extension',
        'name' => 'Test Extension',
        'description' => 'This is a test extension',
    ];

    $result = (new SystemHelper)->extensionValidateConfig($config);

    expect($result)->toBe($config);
});

it('detects running on Windows', function() {
    $result = (new SystemHelper)->runningOnWindows();

    expect($result)->toBe(PHP_OS_FAMILY === 'Windows');
});

it('detects running on Mac', function() {
    $result = (new SystemHelper)->runningOnMac();

    expect($result)->toBe(PHP_OS_FAMILY === 'Darwin');
});

it('detects running on Linux', function() {
    $result = (new SystemHelper)->runningOnLinux();

    expect($result)->toBe(PHP_OS_FAMILY === 'Linux');
});

it('resolves installation url from app url', function(): void {
    config(['app.url' => 'https://example.com/']);

    expect((new SystemHelper)->resolveUrl())->toBe('https://example.com');
});

it('throws when app url is missing', function(): void {
    config(['app.url' => null]);

    expect(fn() => (new SystemHelper)->resolveUrl())
        ->toThrow(ApplicationException::class);
});

it('builds composer platform header lines', function(): void {
    config(['app.url' => 'https://example.com']);

    expect((new SystemHelper)->composerHeaderLines())
        ->toBe([
            'X-Igniter-Platform: php:'.PHP_VERSION.';version:'.Igniter::version().';url:https://example.com',
        ]);
});
