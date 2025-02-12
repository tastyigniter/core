<?php

namespace Igniter\Tests\Flame\Assetic\Asset;

use Igniter\Flame\Assetic\Asset\BaseAsset;
use Igniter\Flame\Assetic\Filter\CssImportFilter;
use Igniter\Flame\Assetic\Filter\FilterInterface;
use InvalidArgumentException;
use RuntimeException;

beforeEach(function() {
    $filter = new CssImportFilter;
    $this->asset = new class([$filter], 'root', 'path', ['var']) extends BaseAsset
    {
        public function load(?FilterInterface $additionalFilter = null)
        {
            return 'content';
        }

        public function getLastModified(): ?int
        {
            return 12345;
        }
    };
});

it('clones correctly', function() {
    expect((clone $this->asset)->getFilters())->toBe($this->asset->getFilters());
});

it('ensures filter is added to the collection', function() {
    $filter = new CssImportFilter;

    $this->asset->ensureFilter($filter);
    expect($this->asset->getFilters())->toContain($filter);
});

it('clears all filters from the collection', function() {
    $this->asset->clearFilters();
    expect($this->asset->getFilters())->toBeEmpty();
});

it('dumps asset content with additional filter', function() {
    $this->asset->setContent('content');

    expect($this->asset->getContent())->toBe('content')
        ->and($this->asset->dump(new CssImportFilter))->toBe('content');
});

it('returns source root of the asset', function() {
    expect($this->asset->getSourceRoot())->toBe('root');
});

it('returns source path of the asset', function() {
    expect($this->asset->getSourcePath())->toBe('path');
});

it('returns source directory of the asset', function() {
    expect($this->asset->getSourceDirectory())->toBe('root');
});

it('sets and gets target path with variables', function() {
    $this->asset->setTargetPath('target/{var}');
    expect($this->asset->getTargetPath())->toBe('target/{var}');
});

it('throws exception when setting target path without required variables', function() {
    expect(fn() => $this->asset->setTargetPath('target'))->toThrow(RuntimeException::class);
});

it('sets and gets values for variables', function() {
    $this->asset->setValues(['var' => 'value']);
    expect($this->asset->getValues())->toBe(['var' => 'value'])
        ->and($this->asset->getVars())->toBe(['var']);
});

it('throws exception when setting values for non-existent variables', function() {
    expect(fn() => $this->asset->setValues(['nonexistent' => 'value']))->toThrow(InvalidArgumentException::class);
});
