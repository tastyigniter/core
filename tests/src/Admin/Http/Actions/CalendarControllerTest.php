<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Http\Actions;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Http\Actions\CalendarController;
use Igniter\Admin\Widgets\Calendar;
use Igniter\Admin\Widgets\Toolbar;

beforeEach(function() {
    $this->controller = new class extends AdminController
    {
        public array $implement = [
            CalendarController::class,
        ];

        public $calendarConfig = [
            'calendar' => [
                'title' => 'Calendar Title',
                'emptyMessage' => 'No events found',
                'popoverPartial' => 'tests.admin::_partials/test-partial',
                'configFile' => [
                    'calendar' => [
                        'toolbar' => [],
                    ],
                ],
            ],
        ];
    };
    $this->controller->widgets['toolbar'] = new Toolbar($this->controller);
    $this->calendarController = new CalendarController($this->controller);
});

it('runs calendar action method without errors', function() {
    $this->calendarController->calendar();

    $widget = $this->calendarController->getCalendarWidget();

    expect($widget)->toBeInstanceOf(Calendar::class);
});

it('renders calendar without errors', function() {
    $this->calendarController->calendar();

    expect($this->calendarController->renderCalendar())->toBeString();
});

it('renders calendar toolbar without errors', function() {
    $this->calendarController->calendar();

    expect($this->calendarController->renderCalendarToolbar())->toBeString();
});

it('generates calender events without errors', function() {
    $this->calendarController->calendar();

    $calendarWidget = $this->calendarController->getCalendarWidget();

    expect($calendarWidget->onGenerateEvents())->toBeArray();
});

it('updates calender events without errors', function() {
    $this->calendarController->calendar();

    $calendarWidget = $this->calendarController->getCalendarWidget();

    expect($calendarWidget->onUpdateEvent())->toBeNull();
});
