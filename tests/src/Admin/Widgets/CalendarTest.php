<?php

namespace Tests\Admin\Widgets;

use Igniter\Admin\Widgets\Calendar;
use Igniter\Flame\Exception\SystemException;
use Igniter\System\Facades\Assets;
use Illuminate\View\Factory;
use Tests\Admin\Fixtures\Controllers\TestController;

dataset('initialization', [
    ['aspectRatio', 2],
    ['editable', true],
    ['eventLimit', 5],
    ['defaultDate', null],
    ['popoverPartial', null],
]);

beforeEach(function () {
    $this->controller = resolve(TestController::class);
    $this->calendarWidget = new Calendar($this->controller);
});

it('initializes correctly', function ($property, $expected) {
    expect($this->calendarWidget->{$property})->toEqual($expected);
})->with('initialization');

it('loads assets correctly', function () {
    Assets::shouldReceive('addJs')->once()->with('js/vendor.datetime.js', 'vendor-datetime-js');
    Assets::shouldReceive('addCss')->once()->with('formwidgets/datepicker.css', 'datepicker-css');
    Assets::shouldReceive('addJs')->once()->with('js/locales/fullcalendar/locales-all.min.js', 'fullcalendar-locales-js');
    Assets::shouldReceive('addJs')->once()->with('calendar.js', 'calendar-js');
    Assets::shouldReceive('addCss')->once()->with('calendar.css', 'calendar-css');

    $this->calendarWidget->assetPath = [];

    $this->calendarWidget->loadAssets();
});

it('renders correctly', function () {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('calendar/calendar'));

    expect($this->calendarWidget->render())->toBeString();
})->throws(\Exception::class);

it('prepares variables correctly', function () {
    $this->calendarWidget->prepareVars();

    expect($this->calendarWidget->vars)
        ->toHaveKey('aspectRatio')
        ->toHaveKey('editable')
        ->toHaveKey('defaultDate')
        ->toHaveKey('eventLimit');
});

it('generates events correctly', function () {
    $result = $this->calendarWidget->onGenerateEvents();

    expect($result)
        ->toBeArray()
        ->toHaveKey('generatedEvents');
});

it('updates events correctly', function () {
    $eventTriggered = false;
    $this->calendarWidget->bindEvent('calendar.updateEvent', function () use (&$eventTriggered) {
        $eventTriggered = true;
    });

    $this->calendarWidget->onUpdateEvent();

    expect($eventTriggered)->toBeTrue();
});

it('renders popover partial correctly', function () {
    expect($this->calendarWidget->renderPopoverPartial())->toBeString();
})->throws(SystemException::class);