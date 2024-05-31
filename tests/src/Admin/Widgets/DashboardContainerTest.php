<?php

namespace Igniter\Tests\Admin\Widgets;

use Igniter\Admin\Widgets\DashboardContainer;
use Igniter\System\Facades\Assets;
use Igniter\User\Facades\AdminAuth;
use Igniter\User\Models\User;
use Illuminate\View\Factory;
use Tests\Admin\Fixtures\Controllers\TestController;

dataset('initialization', [
    ['canManage', true],
    ['canSetDefault', false],
    ['dateRangeFormat', 'MMMM D, YYYY hh:mm A'],
    ['startDate', null],
    ['endDate', null],
]);

beforeEach(function() {
    AdminAuth::shouldReceive('getUser')->andReturn(User::factory()->create());

    $this->controller = resolve(TestController::class);
    $this->dashboardContainerWidget = new DashboardContainer($this->controller, [
        'defaultWidgets' => [
            'onboarding' => [
                'priority' => 10,
                'width' => '6',
            ],
        ],
    ]);
});

it('initializes correctly', function($property, $expected) {
    expect($this->dashboardContainerWidget->{$property})->toEqual($expected);
})->with('initialization');

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('js/vendor.datetime.js', 'vendor-datetime-js');
    Assets::shouldReceive('addCss')->once()->with('formwidgets/datepicker.css', 'datepicker-css');
    Assets::shouldReceive('addCss')->once()->with('dashboardcontainer.css', null);
    Assets::shouldReceive('addJs')->once()->with('dashboardcontainer.js', null);

    $this->dashboardContainerWidget->assetPath = [];

    $this->dashboardContainerWidget->loadAssets();
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('dashboardcontainer/dashboardcontainer'));

    expect($this->dashboardContainerWidget->render())->toBeString()
        ->and($this->dashboardContainerWidget->vars)
        ->toHaveKey('startDate')
        ->toHaveKey('endDate')
        ->toHaveKey('dateRangeFormat');
})->throws(\Exception::class);

it('renders widgets without errors', function() {
    expect($this->dashboardContainerWidget->onRenderWidgets())
        ->toHaveKey('#'.$this->dashboardContainerWidget->getId('container'))
        ->and($this->dashboardContainerWidget->vars)
        ->toHaveKey('widgets');
});

it('loads add popup', function() {
    expect($this->dashboardContainerWidget->onLoadAddPopup())
        ->toHaveKey('#'.$this->dashboardContainerWidget->getId('new-widget-modal-content'))
        ->and($this->dashboardContainerWidget->vars)
        ->toHaveKey('gridColumns')
        ->toHaveKey('widgets');
});

it('loads update popup', function() {
    $widgetAlias = 'onboarding';
    request()->request->add(['widgetAlias' => $widgetAlias]);

    expect($this->dashboardContainerWidget->onLoadUpdatePopup())
        ->toHaveKey('#'.$widgetAlias.'-modal-content')
        ->and($this->dashboardContainerWidget->vars)
        ->toHaveKey('widget')
        ->toHaveKey('widgetAlias');
});

it('resets widgets', function() {
    expect($this->dashboardContainerWidget->onResetWidgets())
        ->toBeArray()
        ->toHaveKey('#'.$this->dashboardContainerWidget->getId('container-list'));
});

it('sets as default', function() {
    $this->dashboardContainerWidget->canSetDefault = true;

    expect($this->dashboardContainerWidget->onSetAsDefault())->toBeNull();
});

it('adds widget', function() {
    request()->request->add([
        'widget' => 'onboarding',
        'size' => 6,
    ]);

    expect($this->dashboardContainerWidget->onAddWidget())
        ->toBeArray()
        ->toHaveKey('@#'.$this->dashboardContainerWidget->getId('container-list'));
});

it('updates widget', function() {
    $widgetAlias = 'onboarding';
    request()->request->add(['alias' => $widgetAlias]);

    $this->dashboardContainerWidget->canManage = true;

    expect($this->dashboardContainerWidget->onUpdateWidget())
        ->toHaveKey('#'.$this->dashboardContainerWidget->getId('container'))
        ->and($this->dashboardContainerWidget->vars)
        ->toHaveKey('widgets');
});

it('removes widget', function() {
    request()->request->add(['alias' => 'onboarding']);

    expect($this->dashboardContainerWidget->onRemoveWidget())->toBeNull();
});

it('sets widget priorities', function() {
    request()->request->add([
        'aliases' => ['onboarding', 'news'],
    ]);

    expect($this->dashboardContainerWidget->onSetWidgetPriorities())->toBeNull();
});

it('sets date range', function() {
    expect($this->dashboardContainerWidget->onSetDateRange())
        ->toHaveKey('#'.$this->dashboardContainerWidget->getId('container'))
        ->and($this->dashboardContainerWidget->vars)
        ->toHaveKey('widgets');
});
