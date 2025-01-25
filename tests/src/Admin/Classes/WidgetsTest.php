<?php

namespace Igniter\Tests\Admin\Classes;

use Igniter\Admin\Classes\Widgets;
use Igniter\System\Classes\BaseExtension;
use Igniter\System\Classes\ExtensionManager;
use Igniter\Tests\Fixtures\Widgets\TestWidget;

beforeEach(function() {
    $this->testExtension = $this->createMock(BaseExtension::class);
    $this->extensionManager = $this->createMock(ExtensionManager::class);
    $this->widgets = new Widgets($this->extensionManager);
});

it('tests listBulkActionWidgets', function() {
    $this->extensionManager->method('getRegistrationMethodValues')
        ->willReturn([
            'TestExtension' => [
                'TestWidget' => ['code' => 'testwidget'],
            ],
        ]);

    $widgets = $this->widgets->listBulkActionWidgets();

    expect($widgets)->toBeArray()->toHaveKey('TestWidget');
});

it('tests registerBulkActionWidget', function() {
    $this->widgets->registerBulkActionWidget('TestWidget', ['code' => 'testwidget']);

    $widgets = $this->widgets->listBulkActionWidgets();

    expect($widgets)->toBeArray()->toHaveKey('TestWidget');
});

it('tests resolveBulkActionWidget with code', function() {
    $this->widgets->registerBulkActionWidgets(function($manager) {
        $manager->registerBulkActionWidget(TestWidget::class, ['code' => 'testwidget']);
    });

    $widget = $this->widgets->resolveBulkActionWidget('testwidget');

    expect($widget)->toBe(TestWidget::class)
        ->and($this->widgets->resolveBulkActionWidget('invalid-widget'))->toBe('invalid-widget');
});

it('tests resolveBulkActionWidget with class name', function() {
    $this->widgets->registerBulkActionWidgets(function($manager) {
        $manager->registerBulkActionWidget(TestWidget::class, []);
    });

    $widget = $this->widgets->resolveBulkActionWidget(TestWidget::class);

    expect($widget)->toBe(TestWidget::class);
});

it('tests listFormWidgets', function() {
    $this->testExtension->method('registerFormWidgets')
        ->willReturn([
            'TestWidget' => ['code' => 'testwidget'],
        ]);

    $this->extensionManager->method('getExtensions')
        ->willReturn([
            'testextension' => $this->testExtension,
        ]);

    $widgets = $this->widgets->listFormWidgets();

    expect($widgets)->toBeArray()->toHaveKey('TestWidget');
});

it('tests registerFormWidget', function() {
    $this->widgets->registerFormWidget('TestWidget', ['code' => 'testwidget']);

    $widgets = $this->widgets->listFormWidgets();

    expect($widgets)->toBeArray()->toHaveKey('TestWidget');
});

it('tests resolveFormWidget with code', function() {
    $this->widgets->registerFormWidgets(function($manager) {
        $manager->registerFormWidget(TestWidget::class, ['code' => 'testwidget']);
    });

    $widget = $this->widgets->resolveFormWidget('testwidget');

    expect($widget)->toBe(TestWidget::class)
        ->and($this->widgets->resolveFormWidget('invalid-widget'))->toBe('invalid-widget');
});

it('tests resolveFormWidget with class name', function() {
    $this->widgets->registerFormWidgets(function($manager) {
        $manager->registerFormWidget(TestWidget::class, []);
    });

    $widget = $this->widgets->resolveFormWidget(TestWidget::class);

    expect($widget)->toBe(TestWidget::class);
});

it('tests listDashboardWidgets', function() {
    $this->testExtension->method('registerDashboardWidgets')
        ->willReturn([
            'TestWidget' => ['code' => 'testdashboardwidget'],
        ]);

    $this->extensionManager->method('getExtensions')
        ->willReturn([
            'testExtension' => $this->testExtension,
        ]);

    $widgets = $this->widgets->listDashboardWidgets();

    expect($widgets)->toBeArray()->toHaveKey('TestWidget');
});

it('tests registerDashboardWidget', function() {
    $this->widgets->registerDashboardWidget('TestWidget', ['code' => 'testwidget']);

    $widgets = $this->widgets->listDashboardWidgets();

    expect($widgets)->toBeArray()->toHaveKey('TestWidget');
});

it('tests resolveDashboardWidget with code', function() {
    $this->widgets->registerDashboardWidgets(function($manager) {
        $manager->registerDashboardWidget(TestWidget::class, ['code' => 'testwidget']);
    });

    $widget = $this->widgets->resolveDashboardWidget('testwidget');

    expect($widget)->toBe(TestWidget::class)
        ->and($this->widgets->resolveDashboardWidget('invalid-widget'))->toBe('invalid-widget');
});

it('tests resolveDashboardWidget with class name', function() {
    $this->widgets->registerDashboardWidgets(function($manager) {
        $manager->registerDashboardWidget(TestWidget::class, []);
    });

    $widget = $this->widgets->resolveDashboardWidget(TestWidget::class);

    expect($widget)->toBe(TestWidget::class);
});
