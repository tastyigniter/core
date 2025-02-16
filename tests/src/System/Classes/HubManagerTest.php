<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

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
            && $request->hasHeader('X-Igniter-Platform', 'php:'.PHP_VERSION.';version:'.Igniter::version().';url:'.url()->current())
            && $postData['client'] === 'tastyigniter'
            && $postData['server'] === base64_encode(serialize([
                'php' => PHP_VERSION,
                'url' => url()->to('/'),
                'version' => Igniter::version(),
                'host' => gethostname() ?: 'unknown',
            ]))
            && $postData['edge'] === 1
            && $postData['filter'] === 'value'
            && $postData['include'] === 'require';
    });
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
        'code' => 'item1',
    ];
    Http::fake(['https://api.tastyigniter.com/v2/item/detail' => Http::response($expectedResponse)]);

    expect($this->hubManager->getDetail('item', ['itemName']))->toBe($expectedResponse);
});

it('gets detail of an site', function() {
    $expectedResponse = [
        'code' => 'site1',
    ];
    Http::fake(['https://api.tastyigniter.com/v2/site/detail' => Http::response($expectedResponse)]);

    expect($this->hubManager->getDetail('site', ['siteName']))->toBe($expectedResponse);
});

it('gets details of multiple items', function() {
    $expectedResponse = [
        'details' => 'info',
    ];
    Http::fake(['https://api.tastyigniter.com/v2/type/details' => Http::response($expectedResponse)]);

    expect($this->hubManager->getDetails('type', ['item1', 'item2']))->toBe(['details' => 'info']);
});

it('applies items and returns collection', function() {
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
    Http::fake(['https://api.tastyigniter.com/v2/core/apply' => Http::response($expectedResponse)]);
    Igniter::partialMock()->shouldReceive('version')->andReturn('1.0.0');

    $result = $this->hubManager->applyItems($expectedResponse);
    expect($result)->toBeCollection();
});

it('sets carte with key and info', function() {
    $this->hubManager->setCarte('key', ['info']);

    expect(setting()->getPref('carte_key'))->toBe('key')
        ->and(setting()->getPref('carte_info'))->toBe(['info']);
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
    $expectedResponse = ['download' => 'data'];
    Http::fake(['https://api.tastyigniter.com/v2/language/download' => Http::response($expectedResponse, 200, [
        'TI-ETag' => 'etag',
    ])]);

    $result = $this->hubManager->downloadLanguagePack('etag', ['param' => 'value']);
    expect($result)->toBe($expectedResponse);
});

it('downloads language pack throws exception if ETag mismatch', function() {
    Http::fake([
        'https://api.tastyigniter.com/v2/language/download' => Http::response([
            'data' => [],
        ], 200, [
            'TI-ETag' => 'different-etag',
        ]),
    ]);

    expect(fn() => $this->hubManager->downloadLanguagePack('etag', ['param' => 'value']))
        ->toThrow(SystemException::class, 'ETag mismatch, please try again.');
});

it('publishes translations', function() {
    $expectedResponse = ['publish' => 'success'];
    Http::fake(['https://api.tastyigniter.com/v2/language/upload' => Http::response($expectedResponse)]);

    $result = $this->hubManager->publishTranslations('locale', ['pack1', 'pack2']);
    expect($result)->toBe($expectedResponse);
});
