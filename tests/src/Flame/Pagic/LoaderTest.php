<?php

namespace Igniter\Tests\Flame\Pagic;

use Exception;
use Igniter\Flame\Pagic\Environment;
use Igniter\Main\Template\Page;

beforeEach(function() {
    $this->templateLoader = resolve(Environment::class)->getLoader();
});

it('returns markup & content when template source is valid', function() {
    $source = Page::load('tests-theme', 'nested-page');
    $this->templateLoader->setSource($source);

    expect($this->templateLoader->getMarkup($source->getFilePath()))->toContain('Test nested page content')
        ->and($this->templateLoader->getContents($source->getFilePath()))->toContain('Test nested page content')
        ->and($this->templateLoader->getMarkup('tests-theme::_partials.test-partial'))->toContain('This is a test partial content')
        ->and($this->templateLoader->getMarkup('test-partial'))->toContain('This is a test partial content')
        ->and(fn() => $this->templateLoader->getMarkup('invalid_template'))->toThrow(Exception::class);
});

it('returns filename when template source is valid', function() {
    $source = Page::load('tests-theme', 'nested-page');
    $this->templateLoader->setSource($source);

    expect($this->templateLoader->getFilePath())->toEndWith('_pages/nested-page.blade.php')
        ->and($this->templateLoader->getFilename($source->getFilePath()))->toEndWith('_pages/nested-page.blade.php')
        ->and($this->templateLoader->getFilename('tests-theme::_partials.test-partial.php'))->toEndWith('_partials/test-partial.blade.php')
        ->and($this->templateLoader->getFilename('tests-theme::_partials.test-partial.php'))->toEndWith('_partials/test-partial.blade.php');
});

it('returns cache key when template source is valid', function() {
    $source = Page::load('tests-theme', 'nested-page');
    $this->templateLoader->setSource($source);

    expect($this->templateLoader->getCacheKey($source->getFilePath()))->toEndWith('_pages/nested-page.blade.php')
        ->and($this->templateLoader->getCacheKey('tests-theme::_partials.test-partial'))->toEndWith('_partials/test-partial.blade.php')
        ->and($this->templateLoader->getCacheKey(dirname($source->getFilePath()).'/components.blade.php'))->toEndWith('_pages/components.blade.php');
});

it('checks when template exists', function() {
    $source = Page::load('tests-theme', 'nested-page');
    $this->templateLoader->setSource($source);

    expect($this->templateLoader->exists($source->getFilePath()))->toBeTrue()
        ->and($this->templateLoader->exists('tests-theme::_partials.test-partial'))->toBeTrue()
        ->and($this->templateLoader->exists('invalid_template'))->toBeFalse();
});
