<?php

namespace Igniter\Tests\Main\Traits;

use Igniter\Main\Classes\MainController;
use Igniter\Main\Components\BlankComponent;
use Igniter\Main\Template\Page;

it('returns false if component not found', function() {
    expect((new MainController())->renderComponent('nonexistent', [], false))->toBeFalse();
});

it('renders component using onRender method', function() {
    $page = Page::resolveRouteBinding('components');
    $page->loadedComponents['onRenderComponent'] = new class extends \Igniter\System\Classes\BaseComponent
    {
        public function onRender()
        {
            return 'rendered';
        }
    };

    $controller = new MainController();
    $controller->runPage($page);

    expect($controller->renderComponent('onRenderComponent'))->toBe('rendered');
});

it('renders component when condition is true', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController();
    $controller->runPage($page);

    expect($controller->renderComponentWhen(true, 'testComponent'))
        ->toContain('This is a test component partial content');
});

it('renders component unless condition is true', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController();
    $controller->runPage($page);

    expect($controller->renderComponentUnless(false, 'testComponent'))
        ->toContain('This is a test component partial content');
});

it('renders first existing component', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController();
    $controller->runPage($page);

    expect($controller->renderComponentFirst(['component1', 'testComponent'], []))
        ->toContain('This is a test component partial content');
});

it('throws exception when no component is found in array', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController();
    $controller->runPage($page);

    expect(fn() => $controller->renderComponentFirst(['component1', 'component2'], []))
        ->toThrow(new \Exception('None of the components in the given array exist.'));
});

it('checks component exists', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController();
    $controller->runPage($page);
    $page->loadedComponents['blankComponent'] = new BlankComponent($controller->getPageObj(), []);

    expect($controller->hasComponent('testComponent'))->toBeTrue()
        ->and($controller->hasComponent('component1'))->toBeFalse()
        ->and($controller->hasComponent('blankComponent'))->toBeFalse();
});

it('finds component by alias', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController();
    $controller->runPage($page);

    expect($controller->findComponentByAlias('testComponent'))->not()->toBeNull();

    $page->loadedComponents = [];

    expect($controller->findComponentByAlias('testComponent'))->not()->toBeNull();
});

it('finds component by handler', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController();
    $controller->runPage($page);

    expect($controller->findComponentByHandler('onAjaxHandler'))->not()->toBeNull();

    $page->loadedComponents = [];

    expect($controller->findComponentByHandler('onAjaxHandler'))->not()->toBeNull()
        ->and($controller->findComponentByHandler('onInvalidHandler'))->toBeNull();
});

it('finds component by partial', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController();
    $controller->runPage($page);

    expect($controller->findComponentByPartial('default'))->not()->toBeNull();

    $page->loadedComponents = [];

    expect($controller->findComponentByPartial('default'))->not()->toBeNull()
        ->and($controller->findComponentByPartial('nonexistence'))->toBeNull();
});

it('returns configurable component', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController();
    $controller->runPage($page);

    expect($controller->getConfiguredComponent('test::livewire-component'))->toBeArray();
});

it('returns false when component partial is not found', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController();
    $controller->runPage($page);

    expect($controller->renderPartial('@nonexistent', [], false))->toBeFalse();
});

it('returns false when component partial and override is not found', function() {
    $page = Page::resolveRouteBinding('components');
    $controller = new MainController();
    $controller->runPage($page);

    expect($controller->renderPartial('testComponent::nonexistent', [], false))->toBeFalse();
});
