<?php

namespace Tests\Admin\Widgets;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Classes\FormField;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Form;
use Igniter\System\Facades\Assets;
use Illuminate\View\Factory;

beforeEach(function() {
    $this->controller = new class extends AdminController
    {
    };
    $this->formWidget = new Form($this->controller, [
        'toolbar' => [
            'prompt' => 'Search text',
            'mode' => 'all',
        ],
        'fields' => [
            'status_name' => [],
            'notify_customer' => [],
            'status_for' => [],
            'status_comment' => [],
        ],
        'model' => Status::factory()->create(),
    ]);

    $this->formWidget->bindToController();
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('form.js', 'form-js');
    Assets::shouldReceive('addJs')->once()->with('formwidget.js', 'formwidget-js');

    $this->formWidget->assetPath = [];

    $this->formWidget->loadAssets();
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->with($this->stringContains('form/form'));

    expect($this->formWidget->render())->toBeString()
        ->and($this->formWidget->vars)->toBeArray()
        ->toHaveKey('filterAlias')
        ->toHaveKey('filterId')
        ->toHaveKey('onSubmitHandler')
        ->toHaveKey('onClearHandler')
        ->toHaveKey('cssClasses')
        ->toHaveKey('search')
        ->toHaveKey('scopes');
})->throws(\Exception::class);

it('renders field correctly', function() {
    $renderedField = $this->formWidget->renderField('status_name');

    expect($renderedField)->toBeString();
});

it('renders field element correctly', function() {
    $field = new FormField('testField', 'Test Field');

    expect($this->formWidget->renderFieldElement($field))->toBeString();
});

it('sets active tab', function() {
    request()->request->add(['tab' => 'testTab']);

    $this->formWidget->onActiveTab();

    expect($this->formWidget->getSession('activeTab'))->toBe('testTab');
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
    expect($this->formWidget->getField('status_name'))->toBeInstanceOf(FormField::class);

    $this->formWidget->removeField('status_name');

    expect($this->formWidget->getField('status_name'))->toBeNull();
});

it('removes tab', function() {
    $fields = ['testField' => ['label' => 'Test Field', 'tab' => 'Test Tab']];
    $this->formWidget->addTabFields($fields);

    expect($this->formWidget->getField('testField'))->toBeInstanceOf(FormField::class);
    $this->formWidget->removeTab('Test Tab');

    expect($this->formWidget->getField('testField'))->toBeNull();
});

it('makes form field', function() {
    $field = $this->formWidget->makeFormField('testField', ['label' => 'Test Field']);

    expect($field)->toBeInstanceOf(FormField::class)
        ->and($field->fieldName)->toBe('testField')
        ->and($field->label)->toBe('Test Field')
        ->and($field->arrayName)->toBe($this->formWidget->arrayName)
        ->and($field->idPrefix)->toBe($this->formWidget->getId());
});

it('make form field widget', function() {
    $field = new FormField('testField', 'Test Field');
    $field->displayAs('widget', ['widget' => 'colorpicker']);

    $widget = $this->formWidget->makeFormFieldWidget($field);

    expect($widget)->toBeInstanceOf(BaseFormWidget::class);
});

it('gets field name', function($name, $context) {
    [$fieldName, $fieldContext] = $this->formWidget->getFieldName($context ? $name.'@'.$context : $name);

    expect($fieldName)->toBe($name)
        ->and($fieldContext)->toBe($context);
})->with([
    ['testField', null],
    ['testField', 'context'],
]);

it('gets field value', function() {
    request()->request->add(['status_name' => 'Test Value']);

    $value = $this->formWidget->getFieldValue('status_name');

    expect($value)->toBe('Test Value');
});

it('gets field depends', function() {
    $field = new FormField('testField', 'Test Field');
    $field->dependsOn = ['otherField'];

    $depends = $this->formWidget->getFieldDepends($field);

    expect($depends)->toBeArray()->toContain('otherField');
});

it('shows field labels', function() {
    $showLabels = $this->formWidget->showFieldLabels(
        $this->formWidget->getField('status_name')
    );

    expect($showLabels)->toBeTrue();
});

it('gets save data', function() {
    request()->request->add(['status_name' => 'Test Value']);

    $saveData = $this->formWidget->getSaveData();

    expect($saveData)->toBeArray()->toHaveKey('status_name')
        ->and($saveData['status_name'])->toBe('Test Value');
});
