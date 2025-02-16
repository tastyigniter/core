<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Classes;

use BadMethodCallException;
use Igniter\Main\Classes\MainController;
use Igniter\Main\Template\Code\PageCode;
use Igniter\Main\Template\Layout;
use Igniter\Main\Template\Page;
use Igniter\System\Classes\BaseComponent;
use Igniter\Tests\System\Fixtures\TestComponent;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Event;

function createBaseComponent($controller = null)
{
    $page = new Page;
    $layout = new Layout;
    $pageCode = new PageCode($page, $layout, $controller ?? controller());

    return new TestComponent($pageCode);
}

it('initializes with properties', function() {
    $baseComponent = createBaseComponent();
    $baseComponent->setAlias('testAlias');

    expect($baseComponent->getProperties())->toBeArray()
        ->and($baseComponent->getPath())->toContain('igniter.tests::views/_components/testcomponent')
        ->and($baseComponent->initialize())->toBeNull()
        ->and($baseComponent->onRun())->toBeNull()
        ->and($baseComponent->onRender())->toBeNull()
        ->and($baseComponent->getEventHandler('onTest'))->toBe('testAlias::onTest')
        ->and($baseComponent->isHidden())->toBeFalse();
});

it('renders component partial', function() {
    $baseComponent = createBaseComponent();
    $baseComponent->setAlias('testAlias');

    expect($baseComponent->renderPartial('@default'))->toContain('This is a test component partial content');
});

it('runs component event handler', function() {
    Event::fake([
        'main.component.afterRunEventHandler',
    ]);
    $baseComponent = createBaseComponent();
    $baseComponent->setAlias('testAlias');

    expect($baseComponent->runEventHandler('onRun'))->toBeNull();

    Event::assertDispatched('main.component.afterRunEventHandler');
});

it('sets and gets alias', function() {
    $baseComponent = createBaseComponent();

    $baseComponent->setAlias('testAlias');
    expect($baseComponent->getAlias())->toBe('testAlias');
});

it('resolves component', function() {
    $baseComponent = createBaseComponent();
    $component = $baseComponent::resolve('testComponent');

    expect($component)->toBeInstanceOf(BaseComponent::class)
        ->and($component->name)->toBe('testComponent');
});

it('returns component parameter', function() {
    $route = new Route('GET', '/', []);
    request()->setRouteResolver(fn() => $route);
    $route->bind(request());
    $route->setParameter('location', 'value');
    $baseComponent = createBaseComponent();

    expect($baseComponent->param('location'))->toBe('value')
        ->and($baseComponent->param('invalid'))->toBeNull();
});

it('handles dynamic method calls', function() {
    $controller = new class extends MainController
    {
        public function testMethod(): string
        {
            return 'test';
        }
    };
    $baseComponent = createBaseComponent($controller);

    expect($baseComponent->testMethod())->toBe('test');
});

it('throws exception for undefined method calls', function() {
    $baseComponent = createBaseComponent();

    expect(fn() => $baseComponent->undefinedMethod())->toThrow(BadMethodCallException::class);
});

it('converts to string', function() {
    $component = new class extends BaseComponent
    {
        public function initialize() {}
    };

    $component->setAlias('stringAlias');
    expect((string)$component)->toBe('stringAlias');
});
