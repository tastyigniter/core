<?php

namespace Igniter\Tests\Flame\Assetic\Filter;

use Igniter\Flame\Assetic\Asset\AssetCollection;
use Igniter\Flame\Assetic\Asset\AssetInterface;
use Igniter\Flame\Assetic\Factory\AssetFactory;
use Igniter\Flame\Assetic\Filter\ScssphpFilter;

it('compiles SCSS content', function() {
    $asset = mock(AssetInterface::class);
    $asset->shouldReceive('getSourceDirectory')->andReturn('/path/to/source');
    $asset->shouldReceive('getContent')->andReturn('body { color: $color; }');
    $asset->shouldReceive('setContent')->once();

    $filter = new ScssphpFilter();
    $filter->setFormatter('compressed');
    $filter->setVariables(['color' => 'red']);
    $filter->addVariable('size', 'large');
    $filter->setImportPaths(['/path/to/import.css']);
    $filter->addImportPath('/path/to/another-import.css');
    $filter->registerFunction('customFunction', $callable = fn($args) => 'result');

    $filter->filterLoad($asset);
    expect($filter->filterDump($asset))->toBeNull();
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

    $filter = new ScssphpFilter();
    $filter->setFormatter('scss_formatter');
    $filter->addImportPath('/path/to/another-import.css');

    expect($filter->getChildren($factory, $content, __DIR__.'/../fixtures/scss'))->toBeArray();
});
