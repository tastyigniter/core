<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Providers;

use Igniter\Flame\Providers\UrlServiceProvider;

it('forces HTTP schema when policy is force', function() {
    config([
        'igniter-system.urlPolicy' => 'force',
        'app.url' => 'http://localhost',
    ]);

    (new UrlServiceProvider(app()))->forceUrlGeneratorPolicy();

    expect(url()->to('foo'))->toEqual('http://localhost/foo');
});

it('forces HTTPS schema when policy is force', function() {
    config([
        'igniter-system.urlPolicy' => 'force',
        'app.url' => 'https://localhost',
    ]);

    (new UrlServiceProvider(app()))->forceUrlGeneratorPolicy();

    expect(url()->to('foo'))->toEqual('https://localhost/foo');
});

it('forces HTTP schema when policy is insecure', function() {
    config([
        'igniter-system.urlPolicy' => 'insecure',
        'app.url' => 'https://localhost',
    ]);

    (new UrlServiceProvider(app()))->forceUrlGeneratorPolicy();

    expect(url()->to('foo'))->toEqual('http://localhost/foo');
});

it('forces HTTPS schema when policy is secure', function() {
    config([
        'igniter-system.urlPolicy' => 'secure',
        'app.url' => 'http://localhost',
    ]);

    (new UrlServiceProvider(app()))->forceUrlGeneratorPolicy();

    expect(url()->to('foo'))->toEqual('https://localhost/foo');
});
