<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Classes;

use Igniter\Main\Classes\BlockManager;
use Igniter\Main\Classes\PageBuilder;
use Igniter\Main\Classes\SectionLayouts;
use Igniter\Main\Classes\Theme;
use Igniter\System\Classes\ExtensionManager;
use InvalidArgumentException;

it('checks whether a layout exists', function() {
    expect(SectionLayouts::has('cols-3'))->toBeTrue()
        ->and(SectionLayouts::has('center-2'))->toBeTrue()
        ->and(SectionLayouts::has('cols-7'))->toBeFalse();
});

it('returns translated layout options', function() {
    $options = SectionLayouts::options();

    expect($options)->toHaveCount(14)
        ->and(array_keys($options))->toContain('cols-1', 'cols-6', 'sidebar-left-3', 'sidebar-right-2', 'center-3')
        ->and($options['cols-2'])->toBe('2 columns')
        ->and($options['center-3'])->toBe('Wide centre column (3/6/3)');
});

it('returns whether a layout has equal width columns', function() {
    expect(SectionLayouts::isEqual('cols-4'))->toBeTrue()
        ->and(SectionLayouts::isEqual('sidebar-left-2'))->toBeFalse();
});

it('returns the column count of a layout', function() {
    expect(SectionLayouts::columnCount('cols-5'))->toBe(5)
        ->and(SectionLayouts::columnCount('sidebar-right-3'))->toBe(2)
        ->and(SectionLayouts::columnCount('center-2'))->toBe(3)
        ->and(SectionLayouts::columnCount('unknown'))->toBe(1);
});

it('returns the column spans of a layout', function() {
    expect(SectionLayouts::spans('sidebar-left-3'))->toBe([3, 9])
        ->and(SectionLayouts::spans('sidebar-left-4'))->toBe([4, 8])
        ->and(SectionLayouts::spans('sidebar-left-2'))->toBe([2, 10])
        ->and(SectionLayouts::spans('sidebar-right-4'))->toBe([8, 4])
        ->and(SectionLayouts::spans('sidebar-right-3'))->toBe([9, 3])
        ->and(SectionLayouts::spans('sidebar-right-2'))->toBe([10, 2])
        ->and(SectionLayouts::spans('center-3'))->toBe([3, 6, 3])
        ->and(SectionLayouts::spans('center-2'))->toBe([2, 8, 2])
        ->and(SectionLayouts::spans('cols-3'))->toBe([4, 4, 4])
        ->and(SectionLayouts::spans('cols-6'))->toBe([2, 2, 2, 2, 2, 2])
        ->and(SectionLayouts::spans('cols-5'))->toBe([null, null, null, null, null])
        ->and(SectionLayouts::spans('unknown'))->toBe([12]);
});

it('checks whether a column is wide', function() {
    expect(SectionLayouts::isWideColumn('cols-2', 0))->toBeTrue()
        ->and(SectionLayouts::isWideColumn('cols-3', 0))->toBeFalse()
        ->and(SectionLayouts::isWideColumn('cols-5', 0))->toBeFalse()
        ->and(SectionLayouts::isWideColumn('sidebar-left-3', 0))->toBeFalse()
        ->and(SectionLayouts::isWideColumn('sidebar-left-3', 1))->toBeTrue()
        ->and(SectionLayouts::isWideColumn('sidebar-right-4', 0))->toBeTrue()
        ->and(SectionLayouts::isWideColumn('sidebar-right-4', 1))->toBeFalse()
        ->and(SectionLayouts::isWideColumn('center-3', 1))->toBeTrue()
        ->and(SectionLayouts::isWideColumn('center-3', 5))->toBeFalse();
});

it('returns the section settings fields and defaults', function() {
    expect(array_keys(SectionLayouts::settingsFields()))->toBe(array_keys(SectionLayouts::defaultSettings()))
        ->and(SectionLayouts::defaultSettings())->toMatchArray(['container' => 'boxed', 'spacing' => 'md']);
});

it('normalizes section settings', function() {
    expect(SectionLayouts::normalizeSettings('invalid'))->toBe(SectionLayouts::defaultSettings())
        ->and(SectionLayouts::normalizeSettings([
            'container' => 'full',
            'spacing' => 'lg',
            'anchor' => 'about',
            'unknown' => 'value',
        ]))->toBe([
            'container' => 'full',
            'spacing' => 'lg',
            'background_color' => null,
            'background_image' => null,
            'anchor' => 'about',
            'css_class' => null,
            'html_attributes' => null,
        ])
        ->and(SectionLayouts::normalizeSettings(['container' => 'wide', 'spacing' => 'xl']))
        ->toMatchArray(['container' => 'boxed', 'spacing' => 'md']);
});

it('registers a layout from a package', function() {
    SectionLayouts::register('sidebar-left-5', 'Narrow left column (5/7)', [5, 7]);

    expect(SectionLayouts::has('sidebar-left-5'))->toBeTrue()
        ->and(SectionLayouts::spans('sidebar-left-5'))->toBe([5, 7])
        ->and(SectionLayouts::columnCount('sidebar-left-5'))->toBe(2)
        ->and(SectionLayouts::isEqual('sidebar-left-5'))->toBeFalse()
        ->and(SectionLayouts::isWideColumn('sidebar-left-5', 1))->toBeTrue()
        ->and(SectionLayouts::isWideColumn('sidebar-left-5', 0))->toBeFalse()
        ->and(array_key_last(SectionLayouts::options()))->toBe('sidebar-left-5')
        ->and(SectionLayouts::options()['sidebar-left-5'])->toBe('Narrow left column (5/7)');
});

it('registers layouts from extensions', function() {
    $extensionManager = mock(ExtensionManager::class);
    $extensionManager->shouldReceive('getRegistrationMethodValues')->with('registerSectionLayouts')->once()->andReturn([
        'acme.layouts' => ['quarters' => ['label' => 'Quarters', 'spans' => [3, 3, 6]]],
    ]);
    app()->instance(ExtensionManager::class, $extensionManager);

    expect(SectionLayouts::spans('quarters'))->toBe([3, 3, 6])
        ->and(SectionLayouts::has('quarters'))->toBeTrue();
});

it('rejects layouts that do not fill the grid or replace a built in one', function(string $code, array $spans) {
    expect(fn() => SectionLayouts::register($code, 'Bad', $spans))->toThrow(InvalidArgumentException::class);
})->with([
    'short of 12' => ['short', [4, 4]],
    'zero span' => ['zero', [12, 0]],
    'not whole' => ['fraction', [6.5, 5.5]],
    'empty' => ['empty', []],
    'built in' => ['cols-2', [6, 6]],
]);

it('lays out sections with a registered layout', function() {
    SectionLayouts::register('thirds-wide', 'Wide last third', [4, 8]);
    $builder = new PageBuilder(new BlockManager);
    $theme = new Theme(testThemePath(), ['code' => 'tests-theme']);

    $sections = $builder->resolve($theme, $builder->normalize($theme, [[
        'id' => 's_1',
        'layout' => 'thirds-wide',
        'columns' => [['blocks' => []]],
    ]]));

    expect($sections[0]['layout'])->toBe('thirds-wide')
        ->and($sections[0]['columns'])->toHaveCount(2)
        ->and($sections[0]['columns'][0]['span'])->toBe(4)
        ->and($sections[0]['columns'][1]['span'])->toBe(8)
        ->and($sections[0]['equal'])->toBeFalse();
});

it('keeps section html attributes as text', function() {
    expect(SectionLayouts::defaultSettings())->toHaveKey('html_attributes')
        ->and(SectionLayouts::normalizeSettings(['html_attributes' => 'data-x="1"'])['html_attributes'])->toBe('data-x="1"')
        ->and(SectionLayouts::settingsFields())->toHaveKey('html_attributes');
});
