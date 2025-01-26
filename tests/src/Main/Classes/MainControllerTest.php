<?php

namespace Igniter\Tests\Main\Classes;

use Igniter\Flame\Exception\AjaxException;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Pagic\Router;
use Igniter\Main\Classes\MainController;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Code\LayoutCode;
use Igniter\Main\Template\Code\PageCode;
use Igniter\Main\Template\Layout;
use Igniter\Main\Template\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Event;

it('throws exception when theme is not set', function() {
    $themeManager = mock(ThemeManager::class);
    app()->instance(ThemeManager::class, $themeManager);
    $themeManager->shouldReceive('getActiveTheme')->andReturn(null);

    $mainController = new MainController();

    expect(fn() => $mainController->callAction('index', []))
        ->toThrow(FlashException::class, lang('igniter::main.not_found.active_theme'));
});

it('loads assets from parent theme', function() {
    $theme = mock(Theme::class);
    $theme->shouldReceive('hasParent')->andReturnTrue();
    $theme->shouldReceive('getParent')->andReturnSelf();
    $theme->shouldReceive('getAssetPath')->andReturn('path/to/style.css');

    expect((new MainController($theme))->assetPath)->toContain('path/to/style.css');
});

it('returns response with correct status code in remap', function() {
    preparePage();

    $mainController = new MainController();
    $response = $mainController->remap('index', []);

    expect($response->getStatusCode())->toBe(200)
        ->and($mainController->getLayoutObj())->toBeInstanceOf(LayoutCode::class)
        ->and($mainController->getPageObj())->toBeInstanceOf(PageCode::class)
        ->and($mainController->getTheme())->toBeInstanceOf(Theme::class)
        ->and($mainController->getRouter())->toBeInstanceOf(Router::class)
        ->and($mainController->getPage())->toBeInstanceOf(Page::class)
        ->and($mainController->getLayout())->toBeInstanceOf(Layout::class);
});

it('returns response using event in remap', function() {
    preparePage();
    Event::listen('main.controller.beforeResponse', function($controller, $url, $page, $output) {
        return 'test-path';
    });

    expect((new MainController())->remap('index', []))->toContain('test-path');
});

it('throws exception when page layout is not found in runPage', function() {
    $page = mock(Page::class)->makePartial();
    $page->layout = 'nonexistent-layout';

    expect(fn() => (new MainController())->runPage($page))
        ->toThrow(FlashException::class, sprintf(
            lang('igniter::main.not_found.layout_name'), $page->layout,
        ));
});

it('returns rendered page content in runPage', function() {
    $page = mock(Page::class)->makePartial();

    expect((new MainController())->runPage($page))->toBeString();
});

it('returns rendered page content using page.init event in runPage', function() {
    Event::listen('main.page.init', function($controller, $page) {
        return 'test-page-content';
    });

    $page = mock(Page::class)->makePartial();

    expect((new MainController())->runPage($page))->toContain('test-page-content');
});

it('returns rendered page content using page.beforeRenderPage event in runPage', function() {
    Event::listen('main.page.beforeRenderPage', function($controller, $page) {
        return 'test-page-content';
    });

    $page = mock(Page::class)->makePartial();

    expect((new MainController())->runPage($page))->toContain('test-page-content');
});

it('returns response using page.start event in pageCycle', function() {
    Event::listen('main.page.start', function($controller) {
        return 'test-page-content';
    });

    expect((new MainController())->pageCycle())->toContain('test-page-content');
});

it('returns response using page.end event in pageCycle', function() {
    Event::listen('main.page.end', function($controller) {
        return 'test-page-content';
    });

    $page = mock(Page::class)->makePartial();

    expect((new MainController())->runPage($page))->toContain('test-page-content');
});

it('returns response from layout component lifecycle method', function() {
    $page = Page::resolveRouteBinding('layout-with-lifecycle');

    expect((new MainController())->runPage($page))->toBeInstanceOf(RedirectResponse::class);
});

it('returns response from page component lifecycle method', function() {
    $page = Page::resolveRouteBinding('page-with-lifecycle');

    expect((new MainController())->runPage($page))->toBeInstanceOf(RedirectResponse::class);
});

it('returns response with correct status code when in ajax handlers', function() {
    preparePage();
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'testComponent::onAjaxHandler');

    $response = (new MainController())->remap('components', []);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toContain('handler-result');
});

it('returns response with rendered partials when in ajax handlers', function() {
    preparePage();
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'onAjaxHandlerWithStringResponse');
    request()->headers->set('X-IGNITER-REQUEST-PARTIALS', 'test-partial');

    $response = (new MainController())->remap('components', []);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toContain('handler-result')->toContain('This is a test partial content');
});

it('returns json response when in ajax handlers', function() {
    preparePage();
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'testComponent::onAjaxHandlerWithObjectResponse');

    $response = (new MainController())->remap('components', []);

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent()))->toHaveKey('json', 'handler-result');
});

it('returns redirect response when in ajax handlers', function() {
    preparePage();
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'testComponent::onAjaxHandlerWithRedirect');

    $response = (new MainController())->remap('components', []);

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent()))->toHaveKey('X_IGNITER_REDIRECT', 'http://localhost');
});

it('returns flash message in response header when in ajax handlers', function() {
    preparePage();
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'testComponent::onAjaxHandlerWithFlash');
    request()->headers->set('X-IGNITER-REQUEST-FLASH', '1');

    $response = (new MainController())->remap('components', []);

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent()))->toHaveKey('X_IGNITER_FLASH_MESSAGES');
});

it('throws exception when validation fails in ajax handler', function() {
    preparePage();
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'testComponent::onAjaxHandlerWithValidationError');

    expect(fn() => (new MainController())->remap('components', []))
        ->toThrow(AjaxException::class);
});

it('throws exception when ajax handler does not exists', function() {
    preparePage();
    request()->headers->set('X-Requested-With', 'XMLHttpRequest');
    request()->headers->set('X-IGNITER-REQUEST-HANDLER', 'testComponent::onInvalidHandler');

    expect(fn() => (new MainController())->remap('components', []))
        ->toThrow(FlashException::class, sprintf(lang('igniter::main.not_found.ajax_handler'), 'testComponent::onInvalidHandler'));
});

it('returns rendered page content in renderPage', function() {
    expect((new MainController())->renderPage())->toBeString();
});

it('returns page content using event in renderPage', function() {
    Event::listen('main.page.render', function($controller, $contents) {
        return 'test-page-content';
    });

    expect((new MainController())->renderPage())->toContain('test-page-content');
});

it('throws exception when component partial is not found in renderPartial', function() {
    expect(fn() => (new MainController())->renderPartial('testComponent::nonexistent-partial'))
        ->toThrow(FlashException::class, sprintf(lang('igniter::main.not_found.component'), 'testComponent'));
});

it('returns false when component partial is not found in renderPartial', function() {
    expect((new MainController())->renderPartial('testComponent::nonexistent-partial', [], false))->toBeFalse();
});

it('returns false when theme partial is not found in renderPartial', function() {
    expect((new MainController())->renderPartial('nonexistent-partial', [], false))->toBeFalse();
});

it('returns rendered partial content in renderPartial', function() {
    expect((new MainController())->renderPartial('test-partial'))->toContain('This is a test partial content');
});

it('returns rendered partial content using page.beforeRenderPartial event in renderPartial', function() {
    Event::listen('main.page.beforeRenderPartial', function($controller, $contents) {
        return 'test-partial-content';
    });

    expect((new MainController())->renderPartial('custom-partial'))->toContain('test-partial-content');
});

it('returns rendered partial content using page.renderPartial event in renderPartial', function() {
    Event::listen('main.page.renderPartial', function($controller, $name, $partialContent) {
        return 'test-partial-content';
    });

    expect((new MainController())->renderPartial('test-partial'))->toContain('test-partial-content');
});

it('returns rendered partial content in renderPartialWhen', function() {
    expect((new MainController())->renderPartialWhen(true, 'test-partial'))->toContain('This is a test partial content');
});

it('returns rendered partial content in renderPartialUnless', function() {
    expect((new MainController())->renderPartialUnless(false, 'test-partial'))->toContain('This is a test partial content');
});

it('returns rendered partial content in renderPartialFirst', function() {
    expect((new MainController())->renderPartialFirst(['invalid-partial', 'test-partial']))
        ->toContain('This is a test partial content');
});

it('returns rendered partial content in renderPartialEach', function() {
    expect((new MainController())->renderPartialEach('test-partial', [
        ['name' => 'John'],
        ['name' => 'Jane'],
    ], 'name'))->toContain("This is a test partial content\nThis is a test partial content");
});

it('returns rendered component partial content in renderPartial', function() {
    $page = Page::resolveRouteBinding('components');
    $mainController = new MainController();
    $mainController->runPage($page);
    $mainController->setComponentContext($page->loadedComponents['testComponent']);
    $result = $mainController->renderPartial('@default');

    expect($result)->toContain('This is a test component partial content');
});

it('returns rendered content in renderContent', function() {
    expect((new MainController())->renderContent('test-content'))->toContain('This is a test content');
});

it('returns rendered content using page.beforeRenderContent event in renderContent', function() {
    Event::listen('main.page.beforeRenderContent', function($controller, $name) {
        return 'test-content';
    });

    expect((new MainController())->renderContent('custom-content'))->toContain('test-content');
});

it('returns rendered content using page.renderContent event in renderContent', function() {
    Event::listen('main.page.renderContent', function($controller, $name, $fileContent) {
        return $fileContent.'test-content';
    });

    expect((new MainController())->renderContent('test-content'))->toContain("test-content");
});

it('throws exception when content is not found in renderContent', function() {
    expect(fn() => (new MainController())->renderContent('nonexistent-content'))
        ->toThrow(FlashException::class, sprintf(lang('igniter::main.not_found.content'), 'nonexistent-content'));
});

it('returns true when component partial is found', function() {
    $page = Page::resolveRouteBinding('components');
    $mainController = new MainController();
    $mainController->runPage($page);
    $mainController->setComponentContext($page->loadedComponents['testComponent']);

    expect($mainController->hasPartial('@default'))->toBeTrue();
});

it('returns true when theme partial is found', function() {
    expect((new MainController())->hasPartial('test-partial'))->toBeTrue();
});

it('returns false when component partial is not found', function() {
    expect((new MainController())->hasPartial('testComponent::nonexistent-component-partial'))->toBeFalse();
});

it('returns false when theme partial is not found', function() {
    expect((new MainController())->hasPartial('nonexistent-theme-partial'))->toBeFalse();
});

function preparePage(): void
{
    $route = new Route('GET', 'test-path', function() {
        return 'test-path';
    });
    request()->setRouteResolver(fn() => $route);
    $route->bind(request());
    $route->setParameter('_file_', Page::resolveRouteBinding('components'));
}
