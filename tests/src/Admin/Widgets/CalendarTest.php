<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Widgets;

use Igniter\Admin\Widgets\Calendar;
use Igniter\Flame\Exception\SystemException;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->calendarWidget = new Calendar($this->controller);
});

it('initializes correctly', function() {
    expect($this->calendarWidget->aspectRatio)->toBe(2)
        ->and($this->calendarWidget->editable)->toBeTrue()
        ->and($this->calendarWidget->eventLimit)->toBe(5)
        ->and($this->calendarWidget->defaultDate)->toBeNull()
        ->and($this->calendarWidget->popoverPartial)->toBeNull();
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('js/vendor.datetime.js', 'vendor-datetime-js');
    Assets::shouldReceive('addCss')->once()->with('formwidgets/datepicker.css', 'datepicker-css');
    Assets::shouldReceive('addJs')->once()->with('js/locales/fullcalendar/locales-all.min.js', 'fullcalendar-locales-js');
    Assets::shouldReceive('addJs')->once()->with('calendar.js', 'calendar-js');
    Assets::shouldReceive('addCss')->once()->with('calendar.css', 'calendar-css');

    $this->calendarWidget->assetPath = [];

    $this->calendarWidget->loadAssets();
});

it('renders correctly', function() {
    $this->calendarWidget->popoverPartial = 'test-partial';
    expect($this->calendarWidget->render())->toBeString();
});

it('prepares variables correctly', function() {
    $this->calendarWidget->prepareVars();

    expect($this->calendarWidget->vars)
        ->toHaveKey('aspectRatio')
        ->toHaveKey('editable')
        ->toHaveKey('defaultDate')
        ->toHaveKey('eventLimit');
});

it('generates events correctly', function() {
    $result = $this->calendarWidget->onGenerateEvents();

    expect($result)
        ->toBeArray()
        ->toHaveKey('generatedEvents');
});

it('updates events correctly', function() {
    $eventTriggered = false;
    $this->calendarWidget->bindEvent('calendar.updateEvent', function() use (&$eventTriggered) {
        $eventTriggered = true;
    });

    $this->calendarWidget->onUpdateEvent();

    expect($eventTriggered)->toBeTrue();
});

it('renders popover partial correctly', function() {
    $this->calendarWidget->popoverPartial = 'test-partial';
    expect($this->calendarWidget->renderPopoverPartial())->toBeString();
});

it('throws exception when missing popover partial', function() {
    $this->calendarWidget->popoverPartial = null;
    expect(fn() => $this->calendarWidget->renderPopoverPartial())
        ->toThrow(SystemException::class, sprintf(lang('igniter::admin.calendar.missing_partial'), TestController::class));
});
