<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Traits;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Lists;
use Igniter\Tests\Fixtures\Controllers\ListExtendableTestController;
use Illuminate\Support\Facades\Event;

beforeEach(function() {
    $this->controller = resolve(ListExtendableTestController::class);
    $this->listsWidget = new class($this->controller) extends Lists
    {
        public function __construct(protected AdminController $controller) {}
    };
});

it('extends list columns successfully', function() {
    $called = false;
    ListExtendableTestController::extendListColumns(function() use (&$called) {
        $called = true;
    });

    Event::dispatch('admin.list.extendColumns', [$this->listsWidget]);

    expect($called)->toBeTrue();
});

it('extends list query successfully', function() {
    $called = false;
    ListExtendableTestController::extendListQuery(function() use (&$called) {
        $called = true;
    });

    Event::dispatch('admin.list.extendQuery', [$this->listsWidget, Status::query()]);

    expect($called)->toBeTrue();
});
