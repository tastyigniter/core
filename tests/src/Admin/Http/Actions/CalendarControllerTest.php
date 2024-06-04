<?php

namespace Igniter\Tests\Admin\Http\Actions;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Http\Actions\CalendarController;
use Igniter\Admin\Widgets\Calendar;

beforeEach(function() {
    $this->controller = new class extends AdminController {
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

    expect($this->calendarController->renderCalendarToolbar())->toBeNull();
});
