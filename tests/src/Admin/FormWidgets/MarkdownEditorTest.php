<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\MarkdownEditor;
use Igniter\System\Facades\Assets;
use Illuminate\Http\Request;
use Illuminate\View\Factory;
use Igniter\Tests\Admin\Fixtures\Controllers\TestController;
use Igniter\Tests\Admin\Fixtures\Models\TestModel;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('test_field', 'Markdown editor');
    $this->markdownEditorWidget = new MarkdownEditor($this->controller, $this->formField, [
        'model' => new TestModel,
    ]);
});

it('initializes correctly', function() {
    expect($this->markdownEditorWidget->mode)->toBe('tab');
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('markdowneditor/markdowneditor'));

    expect($this->markdownEditorWidget->render())->toBeString();
})->throws(\Exception::class);

it('prepares vars correctly', function() {
    $this->markdownEditorWidget->prepareVars();

    expect($this->markdownEditorWidget->vars)
        ->toHaveKey('mode')
        ->toHaveKey('stretch')
        ->toHaveKey('size')
        ->toHaveKey('name')
        ->toHaveKey('value');
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('js/vendor.editor.js', 'vendor-editor-js');
    Assets::shouldReceive('addCss')->once()->with('markdowneditor.css', 'markdowneditor-css');
    Assets::shouldReceive('addJs')->once()->with('markdowneditor.js', 'markdowneditor-js');

    $this->markdownEditorWidget->assetPath = [];

    $this->markdownEditorWidget->loadAssets();
});

it('refreshes correctly', function() {
    $mockRequest = $this->mock(Request::class);
    $mockRequest->shouldReceive('post')->andReturn([
        'test_field' => '# Test content',
    ]);
    $mockRequest->shouldReceive('setUserResolver')->andReturnNull();
    app()->instance('request', $mockRequest);

    expect($this->markdownEditorWidget->onRefresh())
        ->toBeArray()
        ->toHaveKey('preview');
});
