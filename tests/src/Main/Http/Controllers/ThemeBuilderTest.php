<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Http\Controllers;

use Igniter\Main\Classes\BlockManager;
use Igniter\Main\Models\Theme as ThemeModel;
use Igniter\Main\Models\ThemePageLayout;
use Igniter\Tests\Main\Fixtures\TestTextBlock;
use Igniter\Tests\Main\Fixtures\TestWideFormBlock;

beforeEach(function() {
    ThemeModel::factory()->findOrCreateTestTheme();
    resolve(BlockManager::class)->registerBlocks('tests-theme', [TestTextBlock::class, TestWideFormBlock::class]);
});

it('shows the page builder button on the themes list', function() {
    actingAsSuperUser()
        ->get(route('igniter.main.themes'))
        ->assertOk()
        ->assertSee('themes/builder/tests-theme');
});

it('shows the page builder button on the customise page', function() {
    actingAsSuperUser()
        ->get(route('igniter.main.themes', ['slug' => 'source/tests-theme']))
        ->assertOk()
        ->assertSee('themes/builder/tests-theme');
});

it('loads the page builder for the first buildable page', function() {
    actingAsSuperUser()
        ->get(route('igniter.main.themes', ['slug' => 'builder/tests-theme']))
        ->assertOk()
        ->assertSee('data-control="page-builder"', false)
        ->assertSee('Home sections')
        ->assertSee('b_welcome')
        ->assertSee('themes/builder/tests-theme/checkout');
});

it('loads the page builder for a given page', function() {
    actingAsSuperUser()
        ->get(route('igniter.main.themes', ['slug' => 'builder/tests-theme/checkout']))
        ->assertOk()
        ->assertSee('Checkout sections')
        ->assertSee('wide-form');
});

it('does not load the page builder for unknown pages or themes', function(string $slug, string $message) {
    actingAsSuperUser()
        ->get(route('igniter.main.themes', ['slug' => $slug]))
        ->assertSee($message);
})->with([
    ['builder/tests-theme/missing', 'Page [missing] cannot be edited with the page builder.'],
    ['builder/missing-theme', 'Theme [missing-theme] not found.'],
]);

it('saves the page sections', function() {
    actingAsSuperUser()
        ->post(route('igniter.main.themes', ['slug' => 'builder/tests-theme/home']), [
            'ThemePageLayout' => [
                'sections' => json_encode([
                    ['id' => 's_new', 'layout' => 'cols-2', 'columns' => [
                        ['blocks' => [['id' => 'b_new', 'code' => 'text', 'settings' => ['title' => 'Saved']]]],
                    ]],
                ]),
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onSave',
        ])
        ->assertSee('X_IGNITER_REDIRECT');

    $layout = ThemePageLayout::findFor('tests-theme', 'home');

    expect($layout->sections[0]['id'])->toBe('s_new')
        ->and($layout->sections[0]['columns'])->toHaveCount(2)
        ->and($layout->sections[0]['columns'][0]['blocks'][0]['settings'])->toEqual(['title' => 'Saved', 'body' => null]);
});

it('resets the page to the theme default', function() {
    ThemePageLayout::create(['theme_code' => 'tests-theme', 'page_code' => 'home', 'sections' => []]);

    actingAsSuperUser()
        ->post(route('igniter.main.themes', ['slug' => 'builder/tests-theme/home']), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onReset',
        ])
        ->assertSee('X_IGNITER_REDIRECT');

    expect(ThemePageLayout::findFor('tests-theme', 'home'))->toBeNull();
});

it('handles page builder widget requests', function() {
    actingAsSuperUser()
        ->post(route('igniter.main.themes', ['slug' => 'builder/tests-theme/home']), [
            'code' => 'text',
            'settings' => json_encode(['title' => 'Current']),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'builderFormSections::onLoadBlockForm',
        ])
        ->assertOk()
        ->assertSee('builderFormSections::onValidateBlockForm');
});

it('collapses the admin sidebar and lists pages in a scrollable sidebar', function() {
    $response = actingAsSuperUser()
        ->get(route('igniter.main.themes', ['slug' => 'builder/tests-theme/checkout']))
        ->assertOk();

    $html = $response->getContent();

    expect($html)->not->toContain('class="sidebar border-right')
        ->toContain('id="sidebarMenu"')
        ->toContain('class="offcanvas offcanvas-start px-2 py-3"')
        ->toContain('class="nav-link px-3 ms-n2"')
        ->toContain('<nav class="list-group list-group-flush" tabindex="0" data-page-builder-pages>')
        ->and(substr_count($html, 'list-group-item list-group-item-action'))->toBe(2)
        ->and($html)->toMatch('/list-group-item-action active"\s+aria-current="page"\s+href="[^"]+themes\/builder\/tests-theme\/checkout"/');
});

it('keeps the admin sidebar on other pages', function() {
    actingAsSuperUser()
        ->get(route('igniter.main.themes'))
        ->assertOk()
        ->assertSee('class="sidebar border-right overflow-y-auto"', false)
        ->assertSee('offcanvas-lg offcanvas-start', false)
        ->assertSee('nav-link px-3 ms-n2 d-lg-none', false);
});
