<?php

namespace Igniter\Tests\Flame\Pagic;

use Igniter\Flame\Pagic\ArrayLoader;

it('adds or overrides a template', function() {
    $loader = new ArrayLoader(['template1' => 'content1']);
    $loader->setTemplate('template2', 'content2');
    expect($loader->getContents('template2'))->toBe('content2')
        ->and($loader->getMarkup('template1'))->toBe('content1')
        ->and($loader->getFilename('template1'))->toBe('template1')
        ->and($loader->exists('template1'))->toBeTrue()
        ->and($loader->exists('template2'))->toBeTrue()
        ->and($loader->getFilePath())->toBeNull();
});

it('generates cache key for a template', function() {
    $loader = new ArrayLoader(['template1' => 'content1']);
    expect($loader->getCacheKey('template1'))->toBe('template1:content1')
        ->and(fn() => $loader->getCacheKey('template2'))->toThrow(\InvalidArgumentException::class);
});

it('checks if a template is fresh', function() {
    $loader = new ArrayLoader(['template1' => 'content1']);
    expect($loader->isFresh('template1', time()))->toBeTrue()
        ->and(fn() => $loader->isFresh('template2', time()))->toThrow(\InvalidArgumentException::class);
});
