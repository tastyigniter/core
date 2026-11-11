<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Classes;

use Igniter\Main\Classes\SectionLayouts;

it('checks whether a layout exists', function() {
    expect(SectionLayouts::has('cols-3'))->toBeTrue()
        ->and(SectionLayouts::has('center-2'))->toBeTrue()
        ->and(SectionLayouts::has('cols-7'))->toBeFalse();
});

it('returns translated layout options', function() {
    $options = SectionLayouts::options();

    expect($options)->toHaveCount(13)
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
        ])
        ->and(SectionLayouts::normalizeSettings(['container' => 'wide', 'spacing' => 'xl']))
        ->toMatchArray(['container' => 'boxed', 'spacing' => 'md']);
});
