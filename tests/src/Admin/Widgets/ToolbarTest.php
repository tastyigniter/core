<?php

namespace Tests\Admin\Widgets;

use Igniter\Admin\Classes\ToolbarButton;
use Igniter\Admin\Widgets\Toolbar;
use Illuminate\Support\Facades\Event;
use Illuminate\View\Factory;
use Tests\Admin\Fixtures\Controllers\TestController;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->toolbarWidget = new Toolbar($this->controller, [
        'context' => 'save',
        'buttons' => [
            'save' => [
                'label' => 'Save',
                'context' => ['save'],
                'class' => 'btn btn-primary',
            ],
            'saveClose' => [
                'label' => 'Save & Close',
                'context' => ['save'],
                'class' => 'btn btn-primary',
            ],
        ],
    ]);
});

it('re-initializes without errors', function() {
    $this->toolbarWidget->reInitialize([
        'container' => 'toolbar/test-container',
        'buttons' => [
            'delete' => [
                'label' => 'Delete',
                'context' => ['delete'],
                'class' => 'btn btn-danger',
            ],
        ],
    ]);

    expect($this->toolbarWidget->buttons)->toHaveCount(1)->toHaveKey('delete')
        ->and($this->toolbarWidget->container)->toEqual('toolbar/test-container');
});

it('renders without errors', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));
    $viewMock->method('exists')->with($this->stringContains('toolbar/toolbar'));

    expect($this->toolbarWidget->render())->toBeString();
})->throws(\Exception::class);

it('prepares variables without errors', function() {
    Event::fake();

    $this->toolbarWidget->prepareVars();

    Event::assertDispatched('admin.toolbar.extendButtonsBefore');
    Event::assertDispatched('admin.toolbar.extendButtons');

    expect($this->toolbarWidget->vars)
        ->toBeArray()
        ->toHaveKey('toolbarId')
        ->toHaveKey('cssClasses')
        ->toHaveKey('availableButtons');
});

it('renders button markup without errors', function() {
    $buttonObj = new ToolbarButton('test');
    $buttonObj->displayAs('button', []);

    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('toolbar/button_'.$buttonObj->type));

    $this->expectExceptionMessageMatches('/toolbar\/button_button/');

    $this->toolbarWidget->renderButtonMarkup($buttonObj);
});

it('gets context without errors', function() {
    expect($this->toolbarWidget->getContext())->toEqual('save');
});

it('adds buttons without errors', function() {
    $this->toolbarWidget->prepareVars();

    $this->toolbarWidget->addButtons([
        'test' => [
            'label' => 'Test',
            'class' => 'btn btn-default',
        ],
    ]);

    expect($this->toolbarWidget->allButtons)->toHaveCount(3)->toHaveKey('test');
});

it('adds button without errors', function() {
    $this->toolbarWidget->addButton('test', [
        'label' => 'Test',
        'class' => 'btn btn-default',
    ]);

    expect($this->toolbarWidget->allButtons)->toHaveCount(1)->toHaveKey('test');
});

it('removes button without errors', function() {
    $this->toolbarWidget->prepareVars();

    $this->toolbarWidget->removeButton('save');

    expect($this->toolbarWidget->allButtons)->not->toHaveKey('save');
});

it('merges attributes without errors', function() {
    $this->toolbarWidget->mergeAttributes('save', [
        'class' => 'btn btn-danger',
    ]);

    expect($this->toolbarWidget->buttons['save']['class'])->toEqual('btn btn-danger');
});

it('gets button list without errors', function() {
    $this->toolbarWidget->prepareVars();

    $buttons = $this->toolbarWidget->getButtonList();

    expect($buttons)->toHaveCount(2)->toHaveKey('save')->toHaveKey('saveClose');
});

it('gets active save action without errors', function() {
    expect($this->toolbarWidget->getActiveSaveAction())->toEqual('continue');
});

it('chooses save button action without errors', function() {
    request()->request->add(['toolbar_save_action' => 'save-close']);

    $this->toolbarWidget->onChooseSaveButtonAction();

    expect($this->toolbarWidget->getActiveSaveAction())->toEqual('save-close');
});