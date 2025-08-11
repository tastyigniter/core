<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Widgets;

use Igniter\Admin\Classes\Widgets;
use Igniter\Admin\Widgets\DashboardContainer;
use Igniter\Flame\Exception\FlashException;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\Tests\Fixtures\Widgets\TestDashboardWidget;
use Igniter\User\Facades\AdminAuth;
use Igniter\User\Models\User;

beforeEach(function() {
    AdminAuth::shouldReceive('getUser')->andReturn(User::factory()->create());

    $this->controller = resolve(TestController::class);
    $this->widgetConfig = [
        'defaultWidgets' => [
            'test-dashboard-widget' => [
                'priority' => 20,
                'width' => '6',
            ],
            'onboarding' => [
                'priority' => 10,
                'width' => '6',
            ],
            'invalid' => [
                'priority' => 20,
                'width' => '6',
            ],
        ],
    ];
    $this->dashboardContainerWidget = new DashboardContainer($this->controller, $this->widgetConfig);
});

it('initializes correctly', function() {
    expect($this->dashboardContainerWidget->canManage)->toEqual(true)
        ->and($this->dashboardContainerWidget->canSetDefault)->toEqual(false)
        ->and($this->dashboardContainerWidget->dateRangeFormat)->toEqual('MMMM D, YYYY hh:mm A')
        ->and($this->dashboardContainerWidget->startDate)->toEqual(null)
        ->and($this->dashboardContainerWidget->endDate)->toEqual(null);
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('js/vendor.datetime.js', 'vendor-datetime-js');
    Assets::shouldReceive('addCss')->once()->with('formwidgets/datepicker.css', 'datepicker-css');
    Assets::shouldReceive('addCss')->once()->with('dashboardcontainer.css', null);
    Assets::shouldReceive('addJs')->once()->with('dashboardcontainer.js', null);

    $this->dashboardContainerWidget->assetPath = [];

    $this->dashboardContainerWidget->loadAssets();
});

it('renders correctly', function() {
    expect($this->dashboardContainerWidget->render())->toBeString()
        ->and($this->dashboardContainerWidget->vars)
        ->toHaveKey('startDate')
        ->toHaveKey('endDate')
        ->toHaveKey('dateRangeFormat');
});

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
    $widgetAlias = 'test-dashboard-widget';
    resolve(Widgets::class)->registerDashboardWidget(TestDashboardWidget::class, [
        'code' => $widgetAlias,
        'label' => 'Test Dashboard widget',
    ]);
    request()->request->add(['widgetAlias' => $widgetAlias]);

    $dashboardContainerWidget = new DashboardContainer($this->controller, $this->widgetConfig);

    expect($dashboardContainerWidget->onLoadUpdatePopup())
        ->toHaveKey('#'.$widgetAlias.'-modal-content')
        ->and($dashboardContainerWidget->vars)
        ->toHaveKey('widget')
        ->toHaveKey('widgetAlias');
});

it('loads update popup throws exception when missing widget alias', function() {
    request()->request->add([]);

    expect(fn() => $this->dashboardContainerWidget->onLoadUpdatePopup())
        ->toThrow(FlashException::class, lang('igniter::admin.dashboard.alert_select_widget_to_update'));
});

it('loads update popup throws exception when missing widget', function() {
    $widgetAlias = 'invalid';
    request()->request->add(['widgetAlias' => $widgetAlias]);

    expect(fn() => $this->dashboardContainerWidget->onLoadUpdatePopup())
        ->toThrow(FlashException::class, lang('igniter::admin.dashboard.alert_widget_not_found'));
});

it('resets widgets', function() {
    expect($this->dashboardContainerWidget->onResetWidgets())
        ->toBeArray()
        ->toHaveKey('#'.$this->dashboardContainerWidget->getId('container'));
});

it('sets as default', function() {
    $this->dashboardContainerWidget->canSetDefault = true;

    expect($this->dashboardContainerWidget->onSetAsDefault())->toBeNull();
});

it('sets as default throws exception when canSetDefault is disabled', function() {
    $this->dashboardContainerWidget->canSetDefault = false;

    expect(fn() => $this->dashboardContainerWidget->onSetAsDefault())
        ->toThrow(FlashException::class, lang('igniter::admin.alert_access_denied'));
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
