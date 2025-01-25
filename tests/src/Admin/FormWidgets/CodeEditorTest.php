<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\CodeEditor;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\Tests\Fixtures\Models\TestModel;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('test', 'Code editor');
    $this->codeEditorWidget = new CodeEditor($this->controller, $this->formField, [
        'model' => new TestModel,
    ]);
});

it('initializes correctly', function() {
    expect($this->codeEditorWidget->fullPage)->toBeFalse()
        ->and($this->codeEditorWidget->lineSeparator)->toBeNull()
        ->and($this->codeEditorWidget->mode)->toBe('css')
        ->and($this->codeEditorWidget->theme)->toBe('material')
        ->and($this->codeEditorWidget->readOnly)->toBeFalse();
});

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
    expect($this->codeEditorWidget->render())->toBeString();
});
