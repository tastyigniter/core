<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Classes;

use Igniter\Main\Classes\Theme;

it('returns an empty builder config when the theme has no builder file', function() {
    $theme = new Theme(dirname(testThemePath(), 2).'/builder', ['code' => 'no-builder']);

    expect($theme->getBuilderConfig())->toBe(['pages' => []])
        ->and($theme->getBuildablePages())->toBe([]);
});

it('returns the buildable pages of a theme', function() {
    $theme = new Theme(testThemePath(), ['code' => 'tests-theme']);

    $pages = $theme->getBuildablePages();

    expect(array_keys($pages))->toBe(['home', 'checkout'])
        ->and($pages['home']['label'])->toBe('Home')
        ->and($pages['home']['required'])->toBe([])
        ->and($pages['home']['default'])->toHaveCount(1)
        ->and($pages['checkout'])->toBe(['label' => 'Checkout', 'required' => ['wide-form'], 'default' => [], 'custom' => false])
        ->and($theme->getBuildablePage('checkout'))->toBe($pages['checkout'])
        ->and($theme->getBuildablePage('missing'))->toBeNull()
        ->and($theme->getBuilderConfig())->toBe($theme->getBuilderConfig());
});

it('merges the child theme builder config over the parent', function() {
    $theme = new Theme(dirname(testThemePath(), 2).'/builder/child-theme', [
        'code' => 'tests-child-theme',
        'parent' => 'tests-theme',
    ]);

    $pages = $theme->getBuildablePages();

    expect(array_keys($pages))->toBe(['home', 'checkout', 'about'])
        ->and($pages['home'])->toBe(['label' => 'Child home', 'required' => [], 'default' => [], 'custom' => false])
        ->and($pages['about']['required'])->toBe(['text']);
});
