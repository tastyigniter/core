<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\FormWidgets;

use Exception;
use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\RichEditor;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\Tests\Fixtures\Models\TestModel;
use Illuminate\View\Factory;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('test_field', 'RichEditor');
    $this->richEditorWidget = new RichEditor($this->controller, $this->formField, [
        'model' => new TestModel,
        'toolbarButtons' => 'save|delete',
    ]);
});

it('initializes correctly', function() {
    expect($this->richEditorWidget->fullPage)->toBeFalse()
        ->and($this->richEditorWidget->stretch)->toBeNull()
        ->and($this->richEditorWidget->size)->toBeNull()
        ->and($this->richEditorWidget->toolbarButtons)->toBe('save|delete');
});

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
})->throws(Exception::class);
