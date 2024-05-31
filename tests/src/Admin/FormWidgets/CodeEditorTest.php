<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\CodeEditor;
use Igniter\System\Facades\Assets;
use Illuminate\View\Factory;
use Igniter\Tests\Admin\Fixtures\Controllers\TestController;
use Igniter\Tests\Admin\Fixtures\Models\TestModel;

dataset('initialization', [
    ['fullPage', false],
    ['lineSeparator', null],
    ['mode', 'css'],
    ['theme', 'material'],
    ['readOnly', false],
]);

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('test', 'Code editor');
    $this->codeEditorWidget = new CodeEditor($this->controller, $this->formField, [
        'model' => new TestModel,
    ]);
});

it('initializes correctly', function($property, $expected) {
    expect($this->codeEditorWidget->$property)->toBe($expected);
})->with('initialization');

it('loads assets correctly', function() {
    Assets::shouldReceive('addCss')->once()->with('codeeditor.css', 'codeeditor-css');
    Assets::shouldReceive('addJs')->once()->with('js/vendor.editor.js', 'vendor-editor-js');
    Assets::shouldReceive('addJs')->once()->with('codeeditor.js', 'codeeditor-js');

    $this->codeEditorWidget->assetPath = [];

    $this->codeEditorWidget->loadAssets();
});

it('prepares variables correctly', function() {
    $this->codeEditorWidget->prepareVars();

    expect($this->codeEditorWidget->vars)
        ->toHaveKey('field')
        ->toHaveKey('fullPage')
        ->toHaveKey('stretch')
        ->toHaveKey('size')
        ->toHaveKey('lineSeparator')
        ->toHaveKey('readOnly')
        ->toHaveKey('mode')
        ->toHaveKey('theme')
        ->toHaveKey('name')
        ->toHaveKey('value');
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->expects($this->atLeastOnce())
        ->method('exists')
        ->with($this->stringContains('codeeditor/codeeditor'));

    $this->codeEditorWidget->render();
})->throws(\Exception::class);
