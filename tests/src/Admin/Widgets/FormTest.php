<?php

namespace Igniter\Tests\Admin\Widgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\ColorPicker;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Exception\SystemException;
use Igniter\Local\Facades\Location as LocationFacade;
use Igniter\Local\Models\Location;
use Igniter\System\Facades\Assets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

beforeEach(function() {
    $this->controller = new class extends AdminController {};
    $this->widgetConfig = [
        'toolbar' => [
            'prompt' => 'Search text',
            'mode' => 'all',
        ],
        'fields' => [
            'status_name' => [],
            'test-context@status_name' => 'Status Name',
            'test-context@status_color' => 'colorpicker',
            'notify_customer' => ['span' => 'left'],
            'status_comment' => [
                'span' => 'auto',
            ],
            'status_for' => [
                'permissions' => ['Admin.Statuses'],
            ],
        ],
        'tabs' => [
            'fields' => [
                'status_color' => [
                    'tab' => 'tab1',
                    'span' => 'auto',
                ],
                'status_history' => [
                    'tab' => 'tab2',
                    'span' => 'auto',
                ],
                'out-of-context' => [
                    'context' => ['another-context'],
                ],
            ],
        ],
        'model' => Status::factory()->create(),
        'context' => 'test-context',
    ];
    $this->formWidget = new Form($this->controller, $this->widgetConfig);
    $this->formWidget->bindToController();
});

it('throws exception when initializing with invalid model', function() {
    $this->widgetConfig['model'] = '';

    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.form.missing_model'), $this->controller::class));

    $formWidget = new Form($this->controller, $this->widgetConfig);
    $formWidget->bindToController();
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('form.js', 'form-js');
    Assets::shouldReceive('addJs')->once()->with('formwidget.js', 'formwidget-js');

    $this->formWidget->assetPath = [];

    $this->formWidget->loadAssets();
});

it('renders using section correctly', function() {
    expect($this->formWidget->render(['section' => 'primary']))->toBeString();
});

it('renders correctly', function() {
    expect($this->formWidget->render())->toBeString();
});

it('renders field correctly', function() {
    expect($this->formWidget->renderField('status_name'))->toBeString();
});

it('renders field element correctly', function() {
    $field = new FormField('testField', 'Test Field');

    expect($this->formWidget->renderFieldElement($field))->toBeString();
});

it('throws exception when rendering missing field', function() {
    expect(fn() => $this->formWidget->renderField('missingField'))
        ->toThrow(new SystemException(sprintf(lang('igniter::admin.form.missing_definition'), 'missingField')));
});

it('reloads form correctly', function() {
    Event::fake([
        'admin.form.beforeRefresh',
        'admin.form.refreshFields',
        'admin.form.refresh',
    ]);

    request()->request->add([
        'status_name' => 'Test Value',
    ]);

    expect($this->formWidget->onRefresh())->toBeArray();

    Event::assertDispatched('admin.form.beforeRefresh');
    Event::assertDispatched('admin.form.refreshFields');
    Event::assertDispatched('admin.form.refresh');
});

it('reloads form with filtered fields correctly', function() {
    request()->request->add([
        'status_name' => 'Test Value',
    ]);

    $this->widgetConfig['tabs'] = [];
    $this->widgetConfig['fields'] = [
        'status_name' => [],
        'status_color' => [],
    ];
    $this->widgetConfig['model'] = new class extends Status
    {
        public function filterFields($form, &$allFields, $context)
        {
            unset($allFields['status_color']);
        }
    };
    $formWidget = new Form($this->controller, $this->widgetConfig);
    $formWidget->bindToController();

    expect($formWidget->render())->toBeString()
        ->and($formWidget->getField('status_color'))->toBeNull();
});

it('reloads form with additional fields correctly', function() {
    Event::fake([
        'admin.form.beforeRefresh',
        'admin.form.refreshFields',
    ]);

    request()->request->add([
        'status_name' => 'Test Value',
        'fields' => ['status_color', 'invalid_field'],
    ]);
    $this->formWidget->data = ['status_color' => 'Test Color'];
    Event::listen('admin.form.refresh', function($formWidget) {
        return ['#id-element' => 'Test content'];
    });

    expect($this->formWidget->onRefresh())->toBeArray()
        ->toHaveKey('#id-element')
        ->toHaveKey('#form-field-status-color-group');

    Event::assertDispatched('admin.form.beforeRefresh');
    Event::assertDispatched('admin.form.refreshFields');
});

it('sets and returns active tab', function() {
    request()->request->add(['tab' => 'tab1']);

    $this->formWidget->onActiveTab();

    expect($this->formWidget->getSession('activeTab'))->toBe('tab1');

    $this->formWidget->setActiveTab('tab2');

    expect($this->formWidget->activeTab)->toBe('tab2')
        ->and($this->formWidget->getCookieKey())->toEndWith('test-context');
});

it('adds field correctly', function() {
    $fields = ['testField' => ['label' => 'Test Field']];
    $this->formWidget->addFields($fields);

    expect($this->formWidget->getField('testField'))->toBeInstanceOf(FormField::class);
});

it('adds tab fields correctly', function() {
    $fields = ['testField' => ['label' => 'Test Field', 'tab' => 'Test Tab']];
    $this->formWidget->addTabFields($fields);

    $formField = $this->formWidget->getField('testField');
    expect($formField)->toBeInstanceOf(FormField::class)
        ->and($formField->tab)->toBe('Test Tab');
});

it('removes field', function() {
    $this->formWidget->removeField('status_name');

    expect($this->formWidget->getField('status_name'))->toBeNull()
        ->and($this->formWidget->removeField('invalid-field'))->toBeFalse();
});

it('removes tab', function() {
    $fields = ['testField' => ['label' => 'Test Field', 'tab' => 'Test Tab']];
    $this->formWidget->addTabFields($fields);

    $this->formWidget->removeTab('tab1');
    $this->formWidget->removeTab('Test Tab');

    expect($this->formWidget->getField('testField'))->toBeNull()
        ->and($this->formWidget->getField('status_color'))->toBeNull()
        ->and($this->formWidget->getTab('invalid-tab'))->toBeNull()
        ->and($this->formWidget->getTabs())->toBeArray();
});

it('makes form field', function() {
    $field = $this->formWidget->makeFormField('testField', ['label' => 'Test Field']);

    expect($field)->toBeInstanceOf(FormField::class)
        ->and($field->fieldName)->toBe('testField')
        ->and($field->label)->toBe('Test Field')
        ->and($field->arrayName)->toBe($this->formWidget->arrayName)
        ->and($field->idPrefix)->toBe($this->formWidget->getId());
});

it('make form field with callable options', function() {
    $this->widgetConfig['model'] = new class extends Model
    {
        public function getTestFieldOptions()
        {
            return ['option1' => 'Option 1', 'option2' => 'Option 2'];
        }
    };
    $formWidget = new Form($this->controller, $this->widgetConfig);
    $formWidget->bindToController();

    $field = $formWidget->makeFormField('testField', [
        'label' => 'Test Field',
        'type' => 'select',
    ]);

    expect($field)->toBeInstanceOf(FormField::class)
        ->and($field->options())->not()->toBeEmpty();
});

it('make form field with model getDropdownOptions method', function() {
    $this->widgetConfig['model'] = new class extends Model
    {
        public function getDropdownOptions()
        {
            return ['option1' => 'Option 1', 'option2' => 'Option 2'];
        }
    };
    $formWidget = new Form($this->controller, $this->widgetConfig);
    $formWidget->bindToController();

    $field = $formWidget->makeFormField('testField', [
        'label' => 'Test Field',
        'type' => 'select',
    ]);

    expect($field)->toBeInstanceOf(FormField::class)
        ->and($field->options())->not()->toBeEmpty();
});

it('throws exception when options model method does not exists', function() {
    $field = $this->formWidget->makeFormField('testField', [
        'label' => 'Test Field',
        'type' => 'select',
    ]);

    expect(fn() => $field->options())->toThrow(SystemException::class, sprintf(lang('igniter::admin.form.options_method_not_exists'),
        Status::class, 'getTestFieldOptions', 'testField',
    ));
});

it('throws exception when defined options model method does not exists', function() {
    $field = $this->formWidget->makeFormField('testField', [
        'label' => 'Test Field',
        'type' => 'select',
        'options' => 'invalidOptionsMethod',
    ]);

    expect(fn() => $field->options())->toThrow(SystemException::class, sprintf(lang('igniter::admin.form.options_method_not_exists'),
        Status::class, 'invalidOptionsMethod', 'testField',
    ));
});

it('throws exception when making field with invalid type', function() {
    $this->widgetConfig['fields']['invalid-type'] = ['type' => ['invalid']];

    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.form.field_invalid_type'), 'array'));

    $formWidget = new Form($this->controller, $this->widgetConfig);
    $formWidget->bindToController();
    $formWidget->makeFormField('invalid', ['type' => 'invalid']);
});

it('makes form field with class name type', function() {
    expect($this->formWidget->makeFormField('testField', ['type' => ColorPicker::class]))
        ->toBeInstanceOf(FormField::class);
});

it('makes form field without form widget class as parent', function() {
    expect($this->formWidget->makeFormField('testField', ['type' => 'Exception']))
        ->toBeInstanceOf(FormField::class);
});

it('make form field widget', function() {
    $field = new FormField('testField', 'Test Field');
    $field->displayAs('widget', ['widget' => 'colorpicker']);

    $this->formWidget->makeFormFieldWidget($field);

    expect($this->formWidget->getFormWidget('testField'))->toBeInstanceOf(BaseFormWidget::class)
        ->and($this->formWidget->getFormWidgets())->toBeArray();
});

it('make form field widget with callable options', function() {
    $field = new FormField('testField', 'Test Field');
    $field->displayAs('widget', [
        'widget' => 'colorpicker',
        'options' => [Status::class, 'getDropdownOptionsForOrder'],
    ]);

    $widget = $this->formWidget->makeFormFieldWidget($field);

    expect($widget)->toBeInstanceOf(BaseFormWidget::class)
        ->and($field->options()->isNotEmpty())->toBeTrue();
});

it('throws exception when field widget options model method does not exists', function() {
    $field = new FormField('testField', 'Test Field');
    $field->displayAs('widget', [
        'widget' => 'colorpicker',
        'options' => 'invalidMethod',
    ]);

    $this->formWidget->makeFormFieldWidget($field);

    expect(fn() => $field->options())
        ->toThrow(SystemException::class, sprintf(lang('igniter::admin.form.options_method_not_exists'),
            Status::class, 'invalidMethod', 'testField',
        ));
});

it('returns null when making field widget with invalid type', function() {
    $field = new FormField('testField', 'Test Field');
    $field->displayAs('invalid', ['widget' => 'colorpicker']);

    $widget = $this->formWidget->makeFormFieldWidget($field);

    expect($widget)->toBeNull();
});

it('throws exception when field widget class does not exists', function() {
    $field = new FormField('testField', 'Test Field');
    $field->displayAs('widget', ['widget' => 'invalidClass']);

    $this->expectException(SystemException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.alert_widget_class_name'), 'invalidClass'));

    $this->formWidget->makeFormFieldWidget($field);
});

it('returns field name', function($name, $context) {
    [$fieldName, $fieldContext] = $this->formWidget->getFieldName($context ? $name.'@'.$context : $name);

    expect($fieldName)->toBe($name)
        ->and($fieldContext)->toBe($context);
})->with([
    ['testField', null],
    ['testField', 'context'],
]);

it('returns field value', function() {
    request()->request->add(['status_name' => 'Test Value']);

    expect($this->formWidget->getFieldValue('status_name'))->toBe('Test Value');
});

it('getFieldValue throws exception when field does not exist', function() {
    request()->request->add(['status_name' => 'Test Value']);

    expect(fn() => $this->formWidget->getFieldValue('invalid-field'))
        ->toThrow(SystemException::class, lang(
            'igniter::admin.form.missing_definition',
            ['field' => 'invalid-field'],
        ));
});

it('returns field depends', function() {
    $field = new FormField('testField', 'Test Field');
    $field->dependsOn = ['otherField'];

    $depends = $this->formWidget->getFieldDepends($field);

    expect($depends)->toBeArray()->toContain('otherField');
});

it('shows field labels', function() {
    $showLabels = $this->formWidget->showFieldLabels(
        $this->formWidget->getField('status_name'),
    );

    expect($showLabels)->toBeTrue();
});

it('does not show field labels for section type', function() {
    $field = new FormField('testField', 'Test Field');
    $field->displayAs('section', ['widget' => 'colorpicker']);

    expect($this->formWidget->showFieldLabels($field))->toBeFalse();
});

it('shows field labels for widget type', function() {
    $field = new FormField('testField', 'Test Field');
    $field->displayAs('widget', ['widget' => 'colorpicker']);

    $showLabels = $this->formWidget->showFieldLabels($field);

    expect($showLabels)->toBeTrue();
});

it('returns save data', function() {
    request()->request->add([
        'status_name' => 'Test Value',
        'colors' => ['status_color' => '#000000'],
    ]);
    $this->widgetConfig['fields']['nested[field]'] = [];
    $this->widgetConfig['fields']['colors[status_color]'] = [];
    $this->widgetConfig['fields']['widget'] = ['type' => 'colorpicker'];

    $formWidget = new Form($this->controller, $this->widgetConfig);
    $formWidget->bindToController();
    $saveData = $formWidget->getSaveData();

    expect($saveData)->toBeArray()->toHaveKey('status_name')
        ->and($saveData['status_name'])->toBe('Test Value');
});

it('skips disabled, hidden and private field from save data', function() {
    $this->widgetConfig['fields'] = [
        'status_name' => [],
        'status_for' => ['type' => 'checkboxtoggle'],
        'disabledTestField' => ['disabled' => true],
        'hiddenTestField' => ['hidden' => true],
        'disabledTestFieldWidget' => ['disabled' => true, 'type' => 'colorpicker'],
        '_privateTestField' => [],
    ];
    $formWidget = new Form($this->controller, $this->widgetConfig);
    $formWidget->bindToController();
    request()->request->add([
        'status_name' => 'Test Value',
        'status_for' => null,
    ]);

    $saveData = $formWidget->getSaveData();

    expect($saveData)->toBeArray()->toHaveKey('status_name', 'Test Value')
        ->and($saveData)->toBeArray()->toHaveKey('status_for', null)
        ->and($saveData)->toBeArray()->not()->toHaveKey('disabledTestField')
        ->and($saveData)->toBeArray()->not()->toHaveKey('hiddenTestField')
        ->and($saveData)->toBeArray()->not()->toHaveKey('_privateTestField');
});

it('disables location aware form field', function() {
    LocationFacade::setCurrent(Location::factory()->create());
    $this->widgetConfig['fields'] = [
        'status_name' => ['locationAware' => true],
    ];
    $this->widgetConfig['tabs'] = [];
    $formWidget = new Form($this->controller, $this->widgetConfig);
    $formWidget->bindToController();

    expect($formWidget->getField('status_name')->disabled)->toBeTrue();
});
