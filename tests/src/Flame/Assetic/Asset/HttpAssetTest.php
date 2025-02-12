<?php

namespace Igniter\Tests\Flame\Assetic\Asset;

use Igniter\Flame\Assetic\Asset\HttpAsset;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

it('constructs HttpAsset with valid URL', function() {
    $asset = new HttpAsset('//example.com/asset', []);
    expect($asset->getSourceRoot())->toBe('http://example.com')
        ->and($asset->getSourcePath())->toBe('asset');
});

it('throws exception for invalid URL', function() {
    expect(fn() => new HttpAsset('invalid-url'))->toThrow(InvalidArgumentException::class);
});

it('loads content from valid URL', function() {
    $sourceUrl = 'http://example.com/asset';
    File::shouldReceive('get')->with($sourceUrl)->andReturn('asset content');
    $asset = new HttpAsset($sourceUrl);
    $asset->load();

    expect($asset->getContent())->toBe('asset content');
});

it('throws exception when loading content from invalid URL', function() {
    $sourceUrl = 'http://example.com/asset';
    File::shouldReceive('get')->with($sourceUrl)->andReturn(false);
    $asset = new HttpAsset($sourceUrl);

    expect(fn() => $asset->load())->toThrow(RuntimeException::class);
});

it('ignores errors when loading content from invalid URL if ignoreErrors is true', function() {
    $sourceUrl = 'http://example.com/asset';
    File::shouldReceive('get')->with($sourceUrl)->andReturn(false);
    $asset = new HttpAsset($sourceUrl, [], true);
    $asset->load();

    expect($asset->getContent())->toBe('');
});

it('returns last modified timestamp from valid URL', function() {
    $sourceUrl = 'http://example.com/asset';
    Http::fake([
        $sourceUrl => Http::response('ok', 200, ['Last-Modified' => 'Wed, 21 Oct 2015 07:28:00 GMT']),
    ]);
    $asset = new HttpAsset($sourceUrl);

    expect($asset->getLastModified())->toBe(strtotime('Wed, 21 Oct 2015 07:28:00 GMT'));
});

it('returns null for last modified timestamp from invalid URL', function() {
    $sourceUrl = 'http://example.com/asset';
    Http::fake([
        $sourceUrl => Http::response('ok', 404),
    ]);
    $asset = new HttpAsset($sourceUrl);

    expect($asset->getLastModified())->toBeNull();
});
