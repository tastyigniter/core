<?php

namespace Tests\Admin\Classes;

use Igniter\Admin\Classes\Widgets;
use Igniter\System\Classes\BaseExtension;
use Igniter\System\Classes\ExtensionManager;

beforeEach(function () {
    $this->testExtension = $this->createMock(BaseExtension::class);
    $this->extensionManager = $this->createMock(ExtensionManager::class);
    $this->widgets = new Widgets($this->extensionManager);
});

it('tests listBulkActionWidgets', function () {
    $this->extensionManager->method('getRegistrationMethodValues')
        ->willReturn([
            'TestExtension' => [
                'TestWidget' => ['code' => 'testwidget'],
            ],
        ]);

    $widgets = $this->widgets->listBulkActionWidgets();

    expect($widgets)->toBeArray()->toHaveKey('TestWidget');
});

it('tests registerBulkActionWidget', function () {
    $this->widgets->registerBulkActionWidget('TestWidget', ['code' => 'testwidget']);

    $widgets = $this->widgets->listBulkActionWidgets();

    expect($widgets)->toBeArray()->toHaveKey('TestWidget');
});

it('tests resolveBulkActionWidget', function () {
    $this->widgets->registerBulkActionWidget('TestWidget', ['code' => 'testwidget']);

    $widget = $this->widgets->resolveBulkActionWidget('testwidget');

    expect($widget)->toBe('TestWidget');
});

it('tests listFormWidgets', function () {
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

it('tests registerFormWidget', function () {
    $this->widgets->registerFormWidget('TestWidget', ['code' => 'testwidget']);

    $widgets = $this->widgets->listFormWidgets();

    expect($widgets)->toBeArray()->toHaveKey('TestWidget');
});

it('tests resolveFormWidget', function () {
    $this->widgets->registerFormWidget('TestWidget', ['code' => 'testwidget']);

    $widget = $this->widgets->resolveFormWidget('testwidget');

    expect($widget)->toBe('TestWidget');
});

it('tests listDashboardWidgets', function () {
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

it('tests registerDashboardWidget', function () {
    $this->widgets->registerDashboardWidget('TestWidget', ['code' => 'testwidget']);

    $widgets = $this->widgets->listDashboardWidgets();

    expect($widgets)->toBeArray()->toHaveKey('TestWidget');
});

it('tests resolveDashboardWidget', function () {
    $this->widgets->registerDashboardWidget('TestWidget', ['code' => 'testwidget']);

    $widget = $this->widgets->resolveDashboardWidget('testwidget');

    expect($widget)->toBe('TestWidget');
});