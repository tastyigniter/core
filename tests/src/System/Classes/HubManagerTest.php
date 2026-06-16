<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\System\Classes\HubManager;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;

beforeEach(function() {
    $this->hubManager = resolve(HubManager::class);
});

it('prepares request correctly', function() {
    config([
        'app.url' => 'https://example.com',
        'igniter-system.edgeUpdates' => true,
        'igniter-system.carteKey' => 'carte_key',
    ]);
    App::partialMock()->shouldReceive('runningInConsole')->andReturnFalse();
    $expectedResponse = ['data' => []];
    Http::fake(['https://api.tastyigniter.com/v2/items' => Http::response($expectedResponse)]);

    $this->hubManager->listItems(['filter' => 'value']);

    Http::assertSent(function(Request $request): bool {
        $postData = $request->data();

        return $request->hasHeader('Authorization', 'Bearer carte_key')
            && $request->hasHeader('X-Igniter-Host')
            && $request->hasHeader('X-Igniter-User-Ip')
            && $request->hasHeader('X-Igniter-Platform', 'php:'.PHP_VERSION.';version:'.Igniter::version().';url:https://example.com')
            && $postData['client'] === 'tastyigniter'
            && $postData['server'] === base64_encode(serialize([
                'php' => PHP_VERSION,
                'url' => 'https://example.com',
                'version' => Igniter::version(),
                'host' => gethostname() ?: 'unknown',
            ]))
            && $postData['edge'] === 1
            && $postData['filter'] === 'value'
            && $postData['include'] === 'require';
    });
});

it('throws system exception when api returns error', function() {
    config([
        'app.url' => 'https://example.com',
        'igniter-system.carteKey' => 'carte_key',
    ]);
    Http::fake(['https://api.tastyigniter.com/v2/items' => Http::response([
        'message' => 'Carte key registered to another installation',
        'error_code' => 'installation_mismatch',
    ], 403)]);

    expect(fn() => $this->hubManager->listItems())
        ->toThrow(SystemException::class, 'Carte key registered to another installation');
});

it('throws when app url is missing', function() {
    config([
        'app.url' => null,
        'igniter-system.carteKey' => 'carte_key',
    ]);

    expect(fn() => $this->hubManager->listItems())
        ->toThrow(ApplicationException::class);
});

it('lists items with default filter', function() {
    $expectedResponse = [
        'data' => [
            ['code' => 'item1'],
            ['code' => 'item2'],
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/items' => Http::response($expectedResponse)]);

    expect($this->hubManager->listItems())->toBe($expectedResponse);
});

it('gets detail of an item', function() {
    $expectedResponse = [
        'data' => [
            'code' => 'item1',
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/item/detail' => Http::response($expectedResponse)]);

    expect($this->hubManager->getItemDetail(['itemName']))->toBe(array_get($expectedResponse, 'data'));
});

it('gets details of multiple items', function() {
    $expectedResponse = [
        'details' => 'info',
    ];
    Http::fake(['https://api.tastyigniter.com/v2/item/details' => Http::response($expectedResponse)]);

    expect($this->hubManager->getItemDetails(['item1', 'item2']))->toBe(['details' => 'info']);
});

it('applies installed items correctly', function() {
    $expectedResponse = [
        'data' => [
            [
                'code' => 'item1',
                'type' => 'core',
                'package' => 'item1/package',
                'name' => 'Package1',
                'version' => '1.0.0',
                'author' => 'Sam',
            ],
            [
                'code' => 'item2',
                'type' => 'extension',
                'package' => 'item2/package',
                'name' => 'Package2',
                'version' => '1.0.0',
                'author' => 'Sam',
            ],
        ],
    ];
    Http::fake(['https://api.tastyigniter.com/v2/core/installed' => Http::response($expectedResponse)]);
    Igniter::partialMock()->shouldReceive('version')->andReturn('1.0.0');

    $result = $this->hubManager->applyInstalledItems($expectedResponse);
    expect($result)->toBeArray();
});

it('throws exception if updates endpoint is not configured', function() {
    config(['igniter-system.updatesEndpoint' => null]);

    expect(fn() => $this->hubManager->listItems())->toThrow(SystemException::class, 'Updates endpoint not configured');
});

it('throws exception if response is not ok', function() {
    Http::fake(['https://api.tastyigniter.com/v2/items' => Http::response([
        'message' => 'Error message',
        'errors' => [
            'item' => ['Error message'],
        ],
    ], 500)]);

    expect(fn() => $this->hubManager->listItems())->toThrow(SystemException::class, 'Error message');
});

it('lists languages with filter', function() {
    $expectedResponse = ['language1', 'language2'];
    Http::fake(['https://api.tastyigniter.com/v2/languages' => Http::response($expectedResponse)]);

    $result = $this->hubManager->listLanguages(['filter' => 'value']);
    expect($result)->toBe($expectedResponse);
});

it('gets language by locale', function() {
    $expectedResponse = ['language' => 'info'];
    Http::fake(['https://api.tastyigniter.com/v2/language/locale' => Http::response($expectedResponse)]);

    $result = $this->hubManager->getLanguage('locale');
    expect($result)->toBe($expectedResponse);
});

it('applies language pack', function() {
    $expectedResponse = ['result' => 'success'];
    Http::fake(['https://api.tastyigniter.com/v2/language/apply' => Http::response($expectedResponse)]);

    $result = $this->hubManager->applyLanguagePack('locale', ['item1', 'item2']);
    expect($result)->toBe($expectedResponse);
});

it('downloads language pack with eTag', function() {
    $expectedResponse = ['data' => ['hash' => 'etag']];
    Http::fake(['https://api.tastyigniter.com/v2/language/download' => Http::response($expectedResponse)]);

    $result = $this->hubManager->downloadLanguagePack('etag', ['param' => 'value']);
    expect($result)->toBe($expectedResponse);
});

it('downloads language pack throws exception if ETag mismatch', function() {
    $expectedResponse = ['data' => ['hash' => 'different-etag']];
    Http::fake([
        'https://api.tastyigniter.com/v2/language/download' => Http::response($expectedResponse),
    ]);

    expect(fn() => $this->hubManager->downloadLanguagePack('etag', ['param' => 'value']))
        ->toThrow(SystemException::class, 'ETag mismatch, please try again.');
});

it('publishes translations', function() {
    $expectedResponse = ['message' => 'ok'];
    Http::fake(['https://api.tastyigniter.com/v2/language/upload' => Http::response($expectedResponse)]);

    $result = $this->hubManager->publishTranslations('locale', ['pack1', 'pack2']);
    expect($result)->toBe($expectedResponse);
});
