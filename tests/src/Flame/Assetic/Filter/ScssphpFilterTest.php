<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Assetic\Filter;

use Igniter\Flame\Assetic\Asset\AssetCollection;
use Igniter\Flame\Assetic\Asset\AssetInterface;
use Igniter\Flame\Assetic\Factory\AssetFactory;
use Igniter\Flame\Assetic\Filter\ScssphpFilter;

it('compiles SCSS content', function() {
    $compiled = null;
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceDirectory')->andReturn('/path/to/source');
    $asset->shouldReceive('getContent')->andReturn('body { color: $color; }');
    $asset->shouldReceive('setContent')->once()->andReturnUsing(function(string $css) use (&$compiled) {
        $compiled = $css;
    });

    $filter = new ScssphpFilter;
    $filter->setFormatter('compressed');
    $filter->setVariables(['color' => 'red']);
    $filter->addVariable('enabled', true);
    $filter->setImportPaths(['/path/to/import.css']);
    $filter->addImportPath('/path/to/another-import.css');
    $filter->registerFunction('custom-function', fn($args) => $args[0], ['value']);

    $filter->filterLoad($asset);

    expect($compiled)->toContain('color')
        ->and($compiled)->toContain('red')
        ->and($filter->filterDump($asset))->toBeNull();
});

it('maps legacy formatter aliases to output styles', function() {
    $compiled = null;
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceDirectory')->andReturnNull();
    $asset->shouldReceive('getContent')->andReturn('body { color: blue; }');
    $asset->shouldReceive('setContent')->once()->andReturnUsing(function(string $css) use (&$compiled) {
        $compiled = $css;
    });

    $filter = new ScssphpFilter;
    $filter->setFormatter('scss_formatter_nested');
    $filter->setFormatter('scss_formatter_compressed');
    $filter->setFormatter('scss_formatter_crunched');
    $filter->setFormatter('scss_formatter');

    $filter->filterLoad($asset);

    expect($compiled)->toContain('color');
});

it('extracts children assets', function() {
    $factory = mock(AssetFactory::class);
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceRoot')->andReturn('/root');
    $asset->shouldReceive('getSourcePath')->andReturn('css/style.css');
    $asset->shouldReceive('setTargetPath')->once();
    $asset->shouldReceive('load')->once();
    $asset->shouldReceive('getContent')->andReturn('body { color: $color; }');
    $factory->shouldReceive('createAsset')->andReturn(new AssetCollection([$asset]));
    $content = '@import "main";';
    $fixtures = __DIR__.'/../fixtures/scss';

    $filter = new ScssphpFilter;
    $filter->setFormatter('scss_formatter');
    $filter->addImportPath('/path/to/another-import.css');
    $filter->addImportPath(fn(string $url) => null);

    expect($filter->getChildren($factory, $content, $fixtures))->toBeArray()
        ->and($filter->getChildren($factory, '@import "theme.css";'))->toBe([])
        ->and($filter->getChildren($factory, '@import "main";'))->toBe([])
        ->and($filter->getChildren($factory, '@import "missing";', $fixtures))->toBe([]);
});
