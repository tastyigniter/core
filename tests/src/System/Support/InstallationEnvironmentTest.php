<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Support;

use Igniter\Flame\Exception\ApplicationException;
use Igniter\System\Support\InstallationEnvironment;

it('resolves installation url from app url', function(): void {
    config(['app.url' => 'https://example.com/']);

    expect(InstallationEnvironment::resolveUrl())->toBe('https://example.com');
});

it('throws when app url is missing', function(): void {
    config(['app.url' => null]);

    expect(fn() => InstallationEnvironment::resolveUrl())
        ->toThrow(ApplicationException::class);
});

it('builds composer header lines', function(): void {
    config(['app.url' => 'https://example.com']);

    expect(InstallationEnvironment::composerHeaderLines())
        ->toBe(['X-TI-Installation-Url: https://example.com']);
});

it('resolves author code from package name', function(): void {
    expect(InstallationEnvironment::resolveAuthorCode('acme/my-extension'))
        ->toBe('acme')
        ->and(InstallationEnvironment::resolveAuthorCode())
        ->toBe('tastyigniter');
});
