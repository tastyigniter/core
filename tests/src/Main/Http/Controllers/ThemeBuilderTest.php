<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Http\Controllers;

use Igniter\Admin\Classes\FormField;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Classes\BlockManager;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\FormWidgets\PageBuilder;
use Igniter\Main\Models\Theme as ThemeModel;
use Igniter\Main\Models\ThemePage;
use Igniter\Main\Template\Page;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\Tests\Main\Fixtures\TestTextBlock;
use Igniter\Tests\Main\Fixtures\TestWideFormBlock;
use Illuminate\Support\Facades\Event;

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
        ->get(route('igniter.main.themes', ['slug' => 'edit/tests-theme']))
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
            'ThemePage' => [
                'sections' => json_encode([
                    ['id' => 's_new', 'layout' => 'cols-2', 'columns' => [
                        ['blocks' => [['id' => 'b_new', 'code' => 'text', 'settings' => ['title' => 'Saved']]]],
                    ]],
                ]),
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'builderFormSections::onSavePage',
        ])
        ->assertSee('X_IGNITER_REDIRECT');

    $layout = ThemePage::findFor('tests-theme', 'home');

    expect($layout->sections[0]['id'])->toBe('s_new')
        ->and($layout->sections[0]['columns'])->toHaveCount(2)
        ->and($layout->sections[0]['columns'][0]['blocks'][0]['settings'])->toEqual(['title' => 'Saved', 'body' => null]);
});

it('resets the page to the theme default', function() {
    ThemePage::create(['theme_code' => 'tests-theme', 'page_code' => 'home', 'sections' => []]);

    actingAsSuperUser()
        ->post(route('igniter.main.themes', ['slug' => 'builder/tests-theme/home']), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'builderFormSections::onResetPage',
        ])
        ->assertSee('X_IGNITER_REDIRECT');

    expect(ThemePage::findFor('tests-theme', 'home'))->toBeNull();
});

it('keeps the page settings when resetting the page sections', function() {
    ThemePage::create(['theme_code' => 'tests-theme', 'page_code' => 'home', 'sections' => [], 'settings' => ['title' => 'Welcome']]);

    actingAsSuperUser()
        ->post(route('igniter.main.themes', ['slug' => 'builder/tests-theme/home']), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'builderFormSections::onResetPage',
        ])
        ->assertSee('X_IGNITER_REDIRECT');

    $page = ThemePage::findFor('tests-theme', 'home');
    expect($page->sections)->toBeNull()
        ->and($page->settings)->toBe(['title' => 'Welcome']);
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
        ->and($html)->toMatch('/list-group-item-action position-relative p-0 active">\s*<a[^>]+aria-current="page"\s+href="[^"]+themes\/builder\/tests-theme\/checkout"/')
        ->not->toContain('<div class="card-header">');
});

it('keeps the admin sidebar on other pages', function() {
    actingAsSuperUser()
        ->get(route('igniter.main.themes'))
        ->assertOk()
        ->assertSee('class="sidebar border-right overflow-y-auto"', false)
        ->assertSee('offcanvas-lg offcanvas-start', false)
        ->assertSee('nav-link px-3 ms-n2 d-lg-none', false);
});

/**
 * A writable theme in a temporary folder: a builder page from its config (home),
 * a config page without a file (menus), a page made with the builder (about) and
 * a layout to render pages in.
 */
function makePagesTheme(bool $locked = false, bool $child = true): Theme
{
    ThemeModel::clearThemeInstances();
    $path = sys_get_temp_dir().'/pages-theme-'.uniqid();
    File::makeDirectory($path.'/_meta', 0777, true);
    File::makeDirectory($path.'/_layouts', 0777, true);
    File::makeDirectory($path.'/_pages', 0777, true);
    File::put($path.'/_meta/builder.php', "<?php return ['pages' => ['home' => ['label' => 'Home'], 'menus' => ['label' => 'Menus']]];");
    File::put($path.'/_layouts/default.blade.php', "---\ndescription: Default\n---\n@themePage");
    File::put($path.'/_pages/home.blade.php', "---\ntitle: Home\npermalink: /\nlayout: default\n---\n@themeBlocks");
    File::put($path.'/_pages/about.blade.php', "---\ntitle: 'About us'\npermalink: /about\nlayout: default\n---\n@themeBlocks");

    $theme = new Theme($path, ['code' => 'pages-theme']);
    $theme->locked = $locked;
    $theme->child = $child;
    resolve(ThemeManager::class)->themes['pages-theme'] = $theme;
    resolve(ThemeManager::class)->bootTheme($theme);
    ThemeModel::firstOrCreate(['code' => 'pages-theme'], ['name' => 'Pages Theme', 'status' => 1]);

    return $theme;
}

function builderRequest(string $handler, array $data = [], string $page = 'home')
{
    return actingAsSuperUser()->post(admin_url('themes/builder/pages-theme/'.$page), $data, [
        'X-Requested-With' => 'XMLHttpRequest',
        'X-IGNITER-REQUEST-HANDLER' => 'builderFormSections::'.$handler,
    ]);
}

afterEach(function() {
    ThemeModel::clearThemeInstances();

    if ($theme = resolve(ThemeManager::class)->themes['pages-theme'] ?? null) {
        File::deleteDirectory($theme->getPath());
        unset(resolve(ThemeManager::class)->themes['pages-theme']);
    }
});

it('lists pages made with the builder with their settings, rename and delete actions', function() {
    $theme = makePagesTheme();

    expect($theme->getBuildablePages())->toHaveKeys(['home', 'menus', 'about'])
        ->and($theme->getBuildablePage('about'))->toMatchArray(['label' => 'About us', 'custom' => true])
        ->and($theme->getBuildablePage('home')['custom'])->toBeFalse();

    $html = actingAsSuperUser()->get(admin_url('themes/builder/pages-theme/home'))->assertOk()->getContent();

    expect($html)->toContain('data-handler="builderFormSections::onLoadPageName"')
        // Every page has settings, with or without a file.
        ->and(substr_count($html, 'data-handler="builderFormSections::onLoadPageSettings"'))->toBe(3)
        // Only the page made with the builder can be renamed and deleted.
        ->and(substr_count($html, 'data-request="builderFormSections::onDeletePage"'))->toBe(1)
        ->and($html)->toContain("pageCode: 'about'");
});

it('keeps page settings but hides rename and delete in a locked theme', function() {
    makePagesTheme(locked: true);

    $html = actingAsSuperUser()->get(admin_url('themes/builder/pages-theme/home'))->assertOk()->getContent();

    expect(substr_count($html, 'builderFormSections::onLoadPageSettings'))->toBe(3)
        ->and($html)->not->toContain('onLoadPageName')
        ->not->toContain('onDeletePage');
});

it('shows the customise and create child theme buttons', function() {
    makePagesTheme(locked: true);

    $html = actingAsSuperUser()->get(admin_url('themes/builder/pages-theme/home'))->assertOk()->getContent();

    expect($html)->toContain('href="'.admin_url('themes/edit/pages-theme').'"')
        ->toContain(lang('igniter::main.builder.button_customise'))
        ->toContain('data-request="onCreateChild"');
});

it('hides the create child theme button when the theme does not allow it', function() {
    makePagesTheme(locked: true, child: false);

    $html = actingAsSuperUser()->get(admin_url('themes/builder/pages-theme/home'))->assertOk()->getContent();

    expect($html)->toContain(lang('igniter::main.builder.button_customise'))
        ->not->toContain('data-request="onCreateChild"');
});

it('picks where page settings are saved', function(bool $locked, bool $child, string $page, string $mode) {
    makePagesTheme($locked, $child);

    $widget = new PageBuilder(resolve(TestController::class), new FormField('sections', 'Sections'), [
        'model' => new ThemePage(['theme_code' => 'pages-theme', 'page_code' => 'home']),
        'showPages' => true,
    ]);

    expect($widget->pageSettingsMode($page))->toBe($mode);
})->with([
    'writable theme' => [false, true, 'about', 'file'],
    'locked theme with child themes' => [true, true, 'about', 'child'],
    'locked theme without child themes' => [true, false, 'about', 'data'],
    'page without a file' => [false, true, 'menus', 'data'],
    'locked theme, page without a file' => [true, true, 'menus', 'data'],
]);

it('offers a child theme for page settings in a locked theme', function() {
    makePagesTheme(locked: true);

    builderRequest('onLoadPageSettings', ['recordId' => 'about'])
        ->assertOk()
        ->assertSee(lang('igniter::main.builder.text_theme_locked'))
        ->assertSee('data-request=\\"onCreateChild\\"', false)
        ->assertDontSee('PageSettings[settings][title]', false);

    builderRequest('onSavePageSettings', ['pageCode' => 'about', 'PageSettings' => ['settings' => ['title' => 'X']]])
        ->assertSee(lang('igniter::system.themes.alert_theme_locked'));
});

it('saves page settings in theme pages when the page file cannot be written', function() {
    $theme = makePagesTheme(locked: true, child: false);

    builderRequest('onLoadPageSettings', ['recordId' => 'about'])
        ->assertOk()
        ->assertSee('PageSettings[settings][permalink]', false);

    builderRequest('onSavePageSettings', [
        'pageCode' => 'about',
        'PageSettings' => ['settings' => [
            'title' => 'Our story',
            'permalink' => '/our-story',
            'layout' => 'default',
            'description' => 'How we started',
            'isHidden' => '0',
        ]],
    ])->assertSee('X_IGNITER_REDIRECT');

    expect(ThemePage::findFor('pages-theme', 'about')->settings)->toMatchArray(['title' => 'Our story', 'permalink' => '/our-story'])
        // The page file is untouched, the saved settings apply as the page loads.
        ->and(File::get($theme->getPath().'/_pages/about.blade.php'))->toContain("title: 'About us'");

    ThemeModel::clearThemeInstances();
    $page = $theme->findPageFile('about');
    expect($page->title)->toBe('Our story')
        ->and($page->permalink)->toBe('/our-story')
        ->and($page->description)->toBe('How we started');
});

it('saves settings of a page without a file in theme pages', function() {
    makePagesTheme();

    builderRequest('onLoadPageSettings', ['recordId' => 'menus'])
        ->assertOk()
        ->assertSee('PageSettings[settings][title]', false)
        ->assertDontSee('PageSettings[settings][permalink]', false)
        ->assertDontSee('PageSettings[settings][layout]', false);

    builderRequest('onSavePageSettings', [
        'pageCode' => 'menus',
        'PageSettings' => ['settings' => ['title' => '', 'description' => 'Our menu']],
    ])->assertSee('X_IGNITER_REDIRECT');

    expect(ThemePage::findFor('pages-theme', 'menus')->settings)
        ->toBe(['title' => null, 'description' => 'Our menu']);
});

it('moves page settings saved in theme pages into the page file', function() {
    $theme = makePagesTheme();
    ThemePage::saveSettings('pages-theme', 'about', ['description' => 'Saved while locked']);

    builderRequest('onSavePageSettings', [
        'pageCode' => 'about',
        'PageSettings' => ['settings' => ['title' => 'About us', 'permalink' => '/about', 'description' => 'In the file']],
    ])->assertSee('X_IGNITER_REDIRECT');

    ThemeModel::clearThemeInstances();
    expect(ThemePage::findFor('pages-theme', 'about'))->toBeNull()
        ->and($theme->findPageFile('about')->description)->toBe('In the file');
});

it('creates a child theme from the page builder and opens the builder in it', function() {
    ThemeModel::factory()->findOrCreateTestTheme(['status' => 1]);
    $childTheme = ThemeModel::create(['code' => 'child-theme', 'name' => 'Child Theme', 'status' => 1]);
    $child = new class(__DIR__) extends Theme
    {
        public function getBuildablePages(): array
        {
            return ['home' => ['label' => 'Home', 'required' => [], 'default' => [], 'custom' => false]];
        }
    };
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('createChildTheme')->andReturn($childTheme);
    $themeManager->shouldReceive('paths')->andReturn(['child-theme' => testThemePath()]);
    $themeManager->shouldReceive('findTheme')->andReturn($child);

    actingAsSuperUser()
        ->post(route('igniter.main.themes', ['slug' => 'builder/tests-theme/home']), ['pageCode' => 'home'], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onCreateChild',
        ])
        ->assertSee('themes\\/builder\\/child-theme', false);
});

it('loads the page settings form with its meta fields', function() {
    makePagesTheme();

    builderRequest('onLoadPageSettings', ['recordId' => 'about'])
        ->assertOk()
        ->assertSee('builderFormSections::onSavePageSettings')
        // Switching tabs in the modal does not send a request to remember the tab.
        ->assertSee('data-store-active-tab=\\"false\\"', false)
        ->assertSee('PageSettings[settings][title]', false)
        ->assertSee('PageSettings[settings][permalink]', false)
        ->assertSee('About us');
});

it('lets extensions add tabs to the page settings form', function() {
    makePagesTheme();
    Event::listen('admin.form.extendFields', function($widget): void {
        if (($widget->data->fileSource ?? null) instanceof Page) {
            $widget->addFields(['settings[meta_title]' => ['label' => 'Meta title', 'tab' => 'SEO', 'type' => 'text']], 'primary');
        }
    });

    builderRequest('onLoadPageSettings', ['recordId' => 'home'])
        ->assertOk()
        ->assertSee('PageSettings[settings][meta_title]', false)
        ->assertSee('SEO');
});

it('saves the page settings to the page file', function() {
    $theme = makePagesTheme();

    builderRequest('onSavePageSettings', [
        'pageCode' => 'about',
        'PageSettings' => ['settings' => [
            'title' => 'Our story',
            'permalink' => '/our-story',
            'layout' => 'default',
            'description' => 'How we started',
            'isHidden' => '0',
        ]],
    ])->assertSee('X_IGNITER_REDIRECT');

    $page = $theme->findPageFile('about');
    expect($page->title)->toBe('Our story')
        ->and($page->permalink)->toBe('/our-story')
        ->and($page->description)->toBe('How we started')
        ->and(trim($page->getMarkup()))->toBe('@themeBlocks');
});

it('validates the page settings', function() {
    makePagesTheme();

    builderRequest('onSavePageSettings', [
        'pageCode' => 'about',
        'PageSettings' => ['settings' => ['title' => '', 'permalink' => 'no-slash']],
    ])->assertStatus(406);
});

it('creates a page that renders its blocks', function() {
    $theme = makePagesTheme();

    builderRequest('onCreatePage', ['name' => 'Catering Menu'])->assertSee('themes\/builder\/pages-theme\/catering-menu', false);

    $page = $theme->findPageFile('catering-menu');
    expect($page->title)->toBe('Catering Menu')
        ->and($page->permalink)->toBe('/catering-menu')
        ->and($page->layout)->toBe('default')
        ->and(trim($page->getMarkup()))->toBe('@themeBlocks');
});

it('gives a new page a file name that is not taken', function() {
    $theme = makePagesTheme();

    builderRequest('onCreatePage', ['name' => 'About']);

    expect($theme->findPageFile('about-2'))->not->toBeNull();
});

it('refuses to add pages to a locked theme', function() {
    makePagesTheme(locked: true);

    builderRequest('onCreatePage', ['name' => 'Catering'])->assertSee(lang('igniter::system.themes.alert_theme_locked'));
});

it('renames a page made with the builder and keeps its layout', function() {
    $theme = makePagesTheme();
    ThemePage::create(['theme_code' => 'pages-theme', 'page_code' => 'about', 'sections' => []]);

    builderRequest('onRenamePage', ['pageCode' => 'about', 'name' => 'Our Story'])
        ->assertSee('themes\/builder\/pages-theme\/our-story', false);

    expect($theme->findPageFile('about'))->toBeNull()
        ->and($theme->findPageFile('our-story')->title)->toBe('Our Story')
        ->and(ThemePage::where('theme_code', 'pages-theme')->where('page_code', 'our-story')->exists())->toBeTrue();
});

it('refuses to rename or delete a page the theme defines', function() {
    makePagesTheme();

    builderRequest('onRenamePage', ['pageCode' => 'home', 'name' => 'Start'])
        ->assertSee(lang('igniter::main.builder.alert_page_not_custom'));
    builderRequest('onDeletePage', ['pageCode' => 'home'])
        ->assertSee(lang('igniter::main.builder.alert_page_not_custom'));
});

it('deletes a page made with the builder and its layout', function() {
    $theme = makePagesTheme();
    ThemePage::create(['theme_code' => 'pages-theme', 'page_code' => 'about', 'sections' => []]);

    builderRequest('onDeletePage', ['pageCode' => 'about'])->assertSee('X_IGNITER_REDIRECT');

    expect($theme->findPageFile('about'))->toBeNull()
        ->and(ThemePage::where('theme_code', 'pages-theme')->where('page_code', 'about')->exists())->toBeFalse();
});

it('adds a search field to the add block menu', function() {
    actingAsSuperUser()
        ->get(route('igniter.main.themes', ['slug' => 'builder/tests-theme']))
        ->assertOk()
        ->assertSee('data-column-block-search', false)
        ->assertSee(lang('igniter::main.builder.text_no_blocks_found'));
});
