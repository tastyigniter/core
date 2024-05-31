<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\BaseWidget;
use Igniter\Tests\Admin\Fixtures\Controllers\TestController;
use Igniter\Tests\Admin\Fixtures\Widgets\TestWidget;

beforeEach(function() {
    $this->controller = new TestController();
    $this->widget = new BaseWidget($this->controller, [
        'alias' => 'test-alias',
        'property' => 'Test Widget',
    ]);
});

it('has defined paths', function() {
    $controller = resolve(TestController::class);
    $widget = $controller->makeWidget(TestWidget::class);

    expect('tests.admin::_partials.fixtures/widgets/testwidget')->toBeIn($widget->partialPath)
        ->and('tests.admin::_partials.fixtures/widgets')->toBeIn($widget->partialPath)
        ->and('igniter::css/fixtures/widgets')->toBeIn($widget->assetPath);
});

it('loads a widget', function() {
    $this->widget->bindToController();

    expect($this->widget->alias)->toBe('test-alias')
        ->and($this->widget->getId())->toBe('basewidget-test-alias')
        ->and($this->widget->getId('suffix'))->toBe('basewidget-test-alias-suffix')
        ->and($this->controller->widgets['test-alias'])->toBe($this->widget)
        ->and($this->widget->getEventHandler('onTest'))->toBe('test-alias::onTest')
        ->and($this->widget->getController())->toBe($this->controller)
        ->and($this->widget->reload())->toBeArray();
});

it('can set and get config', function() {
    $this->widget->setConfig(['test' => 'value']);
    expect($this->widget->getConfig('test'))->toBe('value');
});
