<?php

namespace Igniter\Tests\Main\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Pagic\Contracts\TemplateInterface;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\FormWidgets\Components;
use Igniter\Main\Models\Theme;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Illuminate\Http\RedirectResponse;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('components', 'Components');
    $this->formField->displayAs('components');
    $this->formField->arrayName = 'theme';
    $this->componentsWidget = new Components($this->controller, $this->formField, [
        'model' => Theme::factory()->make(['code' => 'tests-theme']),
        'form' => [
            'fields' => [
                'alias' => [
                    'label' => 'igniter::system.themes.label_component_alias',
                    'type' => 'text',
                    'context' => ['edit', 'partial'],
                    'comment' => 'igniter::system.themes.help_component_alias',
                    'attributes' => [
                        'data-toggle' => 'disabled',
                    ],
                ],
                'partial' => [
                    'label' => 'igniter::system.themes.label_override_partial',
                    'type' => 'select',
                    'context' => 'partial',
                    'placeholder' => 'lang:igniter::admin.text_please_select',
                ],
                'pageLimit' => [
                    'label' => 'igniter::system.label_page_limit',
                    'type' => 'number',
                    'context' => 'edit',
                ],
            ],
        ],
    ]);
});

it('initializes correctly', function() {
    expect($this->componentsWidget->form)->toBeArray()
        ->and($this->componentsWidget->prompt)->toBe('igniter::admin.text_please_select')
        ->and($this->componentsWidget->addTitle)->toBe('igniter::main.components.button_new')
        ->and($this->componentsWidget->editTitle)->toBe('igniter::main.components.button_edit')
        ->and($this->componentsWidget->copyPartialTitle)->toBe('igniter::main.components.button_copy_partial');
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
    Assets::shouldReceive('addCss')->once()->with('components.css', 'components-css');
    Assets::shouldReceive('addJs')->once()->with('components.js', 'components-js');

    $this->componentsWidget->assetPath = [];

    $this->componentsWidget->loadAssets();
});

it('renders correctly', function() {
    $this->formField->value = [
        'blankComponent' => [
            'property' => 'value',
        ],
        'invalidComponent' => [
            'property' => 'value',
        ],
    ];

    expect($this->componentsWidget->render())->toBeString();
});

it('returns no save data constant when value is not an array', function() {
    expect($this->componentsWidget->getSaveValue('stringValue'))->toBe(FormField::NO_SAVE_DATA);
});

it('sorts components and returns no save data constant when value is an array', function() {
    $this->componentsWidget->data = (object)['fileSource' => $template = mock(TemplateInterface::class)];
    $template->shouldReceive('sortComponents')->with(['component1', 'component2']);

    expect($this->componentsWidget->getSaveValue(['component1', 'component2']))->toBe(FormField::NO_SAVE_DATA);
});

it('throws exception when loading hidden component', function() {
    request()->request->add([
        'alias' => 'blankComponent',
        'context' => 'edit',
    ]);

    expect(fn() => $this->componentsWidget->onLoadRecord())->toThrow(FlashException::class, 'Selected component is hidden');
});

it('throws exception when loading override partial for configurable component', function() {
    request()->request->add([
        'alias' => 'test::livewire-component',
        'context' => 'partial',
    ]);

    expect(fn() => $this->componentsWidget->onLoadRecord())
        ->toThrow(FlashException::class, 'Selected component is not configurable, hence cannot override partial.');
});

it('loads edit component form correctly', function() {
    request()->request->add([
        'alias' => 'testComponent',
        'context' => 'edit',
    ]);

    expect($this->componentsWidget->onLoadRecord())->toBeString();
});

it('loads override partial form correctly', function() {
    request()->request->add([
        'alias' => 'testComponent',
        'context' => 'partial',
    ]);

    expect($this->componentsWidget->onLoadRecord())->toBeString();
});

it('throws exception when saving with locked theme', function() {
    app()->instance(ThemeManager::class, $themeManager = mock(ThemeManager::class));
    $themeManager->shouldReceive('isLocked')->with('tests-theme')->andReturn(true);

    expect(fn() => $this->componentsWidget->onSaveRecord())
        ->toThrow(FlashException::class, 'This is a locked theme, changes are restricted, create a child theme to make changes.');
});

it('throws exception when saving with invalid component in post request', function() {
    request()->request->add(['recordId' => 'invalidComponent']);
    request()->setMethod('post');

    expect(fn() => $this->componentsWidget->onSaveRecord())->toThrow(FlashException::class, 'Invalid component selected');
});

it('throws exception when saving with invalid component', function() {
    request()->request->add([
        'recordId' => 'invalidComponent',
    ]);

    expect(fn() => $this->componentsWidget->onSaveRecord())->toThrow(FlashException::class, 'Invalid component selected');
});

it('throws exception when saving with invalid template file', function() {
    request()->setMethod('post');
    request()->request->add([
        'recordId' => 'testComponent',
    ]);
    $this->componentsWidget->data = (object)['fileSource' => null];

    expect(fn() => $this->componentsWidget->onSaveRecord())->toThrow(FlashException::class, 'Template file not found');
});

it('throws exception when overriding partial with non existence partial', function() {
    request()->setMethod('post');
    request()->request->add([
        'recordId' => 'testComponent',
        'theme' => [
            'componentData' => [
                'alias' => 'testComponent',
                'partial' => 'invalidPartial',
            ],
        ],
    ]);
    $this->componentsWidget->data = (object)['fileSource' => mock(TemplateInterface::class)];

    expect(fn() => $this->componentsWidget->onSaveRecord())
        ->toThrow(lang('igniter::system.themes.alert_component_partial_not_found'));
});

it('overrides component partial successfully', function() {
    request()->setMethod('post');
    request()->request->add([
        'recordId' => 'testComponent',
        'theme' => [
            'componentData' => [
                'alias' => 'testComponent',
                'partial' => 'testPartial',
            ],
        ],
    ]);
    $this->componentsWidget->data = (object)['fileSource' => mock(TemplateInterface::class)];
    $fileMock = File::partialMock();
    $fileMock->shouldReceive('exists')
        ->withArgs(fn($path) => ends_with($path, '_components/testcomponent/testPartial.blade.php'))
        ->andReturn(true);
    $fileMock->shouldReceive('isDirectory')->andReturnFalse();
    $fileMock->shouldReceive('makeDirectory')->andReturnTrue();
    $fileMock->shouldReceive('copy')->andReturnTrue();

    expect($this->componentsWidget->onSaveRecord())->toBeInstanceOf(RedirectResponse::class);
});

it('adds component correctly', function() {
    request()->setMethod('post');
    request()->request->add([
        'recordId' => 'testComponent',
    ]);
    $this->formField->value = [
        'testComponent' => [
            'property' => 'value',
        ],
    ];
    $this->componentsWidget->data = (object)['fileSource' => $template = mock(TemplateInterface::class)];
    $template->shouldReceive('updateComponent')->once();
    $template->settings = ['components' => []];

    $this->componentsWidget->bindEvent('updated', function($codeAlias) {
        expect($codeAlias)->toBe('testComponent testComponentCopy');
    });

    expect($this->componentsWidget->onSaveRecord())->toBeArray();
});

it('updates component correctly', function() {
    request()->request->add([
        'recordId' => 'testComponent',
        'theme' => [
            'componentData' => [
                'alias' => 'testComponent',
                'pageLimit' => '10',
            ],
        ],
    ]);
    $this->formField->value = [
        'testComponent' => [
            'pageLimit' => '10',
        ],
    ];
    $this->componentsWidget->data = (object)['fileSource' => $template = mock(TemplateInterface::class)];
    $template->shouldReceive('updateComponent')->once();
    $template->settings = ['components' => ['testComponent' => []]];

    expect($this->componentsWidget->onSaveRecord())->toBeArray();
});

it('throws exception when removing component with invalid code', function() {
    expect(fn() => $this->componentsWidget->onRemoveComponent())->toThrow(FlashException::class, 'Invalid component selected');
});

it('throws exception when removing component with missing template file', function() {
    request()->request->add([
        'code' => 'testComponent',
    ]);
    $this->componentsWidget->data = (object)['fileSource' => null];

    expect(fn() => $this->componentsWidget->onRemoveComponent())
        ->toThrow(FlashException::class, 'Template file not found');
});

it('removes component successfully', function() {
    request()->request->add([
        'code' => 'validComponent',
    ]);
    $this->componentsWidget->data = (object)['fileSource' => $template = mock(TemplateInterface::class)];
    $template->shouldReceive('getAttributes')->andReturn([
        'settings' => [
            'components' => [
                'validComponent' => [],
                'testComponent' => [],
            ],
        ],
    ]);
    $template->shouldReceive('setRawAttributes');
    $template->shouldReceive('setAttribute');
    $template->shouldReceive('save');
    $template->settings = ['components' => ['testComponent' => []]];

    expect($this->componentsWidget->onRemoveComponent())->toBeArray()
        ->and($this->formField->value)->toBe(['testComponent' => []]);
});
