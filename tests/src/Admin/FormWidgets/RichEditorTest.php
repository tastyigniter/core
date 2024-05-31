<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\RichEditor;
use Igniter\System\Facades\Assets;
use Illuminate\View\Factory;
use Tests\Admin\Fixtures\Controllers\TestController;
use Tests\Admin\Fixtures\Models\TestModel;

dataset('initialization', [
    ['fullPage', false],
    ['stretch', null],
    ['size', null],
    ['toolbarButtons', null],
]);

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('test_field', 'RichEditor');
    $this->richEditorWidget = new RichEditor($this->controller, $this->formField, [
        'model' => new TestModel,
    ]);
});

it('initializes correctly', function($property, $expected) {
    expect($this->richEditorWidget->$property)->toBe($expected);
})->with('initialization');

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('js/vendor.editor.js', 'vendor-editor-js');
    Assets::shouldReceive('addCss')->once()->with('richeditor.css', 'richeditor-css');
    Assets::shouldReceive('addJs')->once()->with('richeditor.js', 'richeditor-js');

    $this->richEditorWidget->assetPath = [];

    $this->richEditorWidget->loadAssets();
});

it('prepares variables correctly', function() {
    $this->richEditorWidget->prepareVars();

    expect($this->richEditorWidget->vars)
        ->toBeArray()
        ->toHaveKey('field')
        ->toHaveKey('fullPage')
        ->toHaveKey('stretch')
        ->toHaveKey('size')
        ->toHaveKey('name')
        ->toHaveKey('value')
        ->toHaveKey('toolbarButtons');
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('richeditor/richeditor'));

    $this->richEditorWidget->render();
})->throws(\Exception::class);
