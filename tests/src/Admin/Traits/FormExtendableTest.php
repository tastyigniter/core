<?php

namespace Igniter\Tests\Admin\Traits;

use Igniter\Admin\Widgets\Form;
use Igniter\Tests\Fixtures\Controllers\FormExtendableTestController;
use Illuminate\Support\Facades\Event;

beforeEach(function() {
    $this->controller = resolve(FormExtendableTestController::class);
    $this->form = new class($this->controller) extends Form
    {
        public function __construct(protected \Igniter\Admin\Classes\AdminController $controller) {}
    };
});

it('calls callback when extending form fields', function() {
    $called = false;
    FormExtendableTestController::extendFormFields(function() use (&$called) {
        $called = true;
    });

    Event::dispatch('admin.form.extendFields', [$this->form]);

    expect($called)->toBeTrue();
});

it('calls callback when extending form fields before', function() {
    $called = false;
    $callback = function() use (&$called) {
        $called = true;
    };

    FormExtendableTestController::extendFormFieldsBefore($callback);
    Event::dispatch('admin.form.extendFieldsBefore', [$this->form]);

    expect($called)->toBeTrue();
});
