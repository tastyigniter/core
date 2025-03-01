<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Models;

use Igniter\System\Models\Translation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

it('updates and locks translation with new text', function() {
    $translation = Translation::create(['text' => 'Old Text', 'locked' => false]);
    $result = $translation->updateAndLock('New Text');

    expect($result)->toBeTrue()
        ->and($translation->fresh()->text)->toBe('New Text')
        ->and($translation->fresh()->locked)->toBeTrue();
});

it('returns correct cache key', function() {
    $key = Translation::getCacheKey('en', 'messages', 'namespace');
    expect($key)->toBe('igniter.translation.en.namespace.messages');
});

it('returns full translation code', function() {
    $translation = new Translation([
        'namespace' => 'namespace',
        'group' => 'group',
        'item' => 'item',
    ]);
    expect($translation->code)->toBe('namespace::group.item');
});

it('flags translation as reviewed', function() {
    $translation = new Translation(['unstable' => true]);
    $translation->flagAsReviewed();

    expect($translation->unstable)->toBeFalse();
});

it('flags translation as unstable', function() {
    $translation = new Translation(['unstable' => false]);
    $translation->flagAsUnstable();

    expect($translation->unstable)->toBeTrue();
});

it('checks translation locks state', function() {
    $translation = new Translation(['locked' => false]);
    $translation->lockState();

    expect($translation->locked)->toBeTrue();

    $translation = new Translation;
    $translation->locked = true;
    expect($translation->isLocked())->toBeTrue();
});

it('retrieves fresh translations', function() {
    expect(Translation::getFresh('en', 'messages', 'namespace'))->toBeInstanceOf(Collection::class);
});

it('retrieves cached translations', function() {
    $translation = new Translation([
        'locale' => 'en',
        'group' => 'messages',
        'namespace' => 'namespace',
        'text' => 'Hello',
    ]);
    $translation->save();

    Cache::shouldReceive('rememberForever')->withArgs(fn($cacheKey, $callback): bool => !empty($callback()))->andReturn([]);
    $translations = Translation::getCached('en', 'messages', 'namespace');
    expect($translations)->toBe([]);
});
