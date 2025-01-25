<?php

namespace Igniter\Tests\Admin\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Admin\FormWidgets\Connector;
use Igniter\Admin\FormWidgets\RecordEditor;
use Igniter\Flame\Exception\FlashException;
use Igniter\System\Facades\Assets;
use Igniter\System\Models\Language;
use Igniter\System\Models\Page;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Illuminate\Database\Eloquent\Model;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('test_field', 'Record editor');
    $this->formField->arrayName = 'language';
    $this->recordEditorWidget = new RecordEditor($this->controller, $this->formField, [
        'model' => Page::factory()->create(),
        'modelClass' => RecordEditorLanguage::class,
        'form' => [
            'fields' => [
                'name' => [],
                'code' => [],
                'idiom' => [],
                'status' => [],
            ],
        ],
    ]);
});

it('initializes correctly when request data exists', function() {
    $language = Language::factory()->create();
    $recordData = [
        'recordId' => $language->getKey(),
        'name' => 'Test Language',
        'code' => 'test-language',
        'idiom' => 'te',
        'status' => true,
    ];
    request()->headers->add([
        'X-IGNITER-RECORD-EDITOR-REQUEST-DATA' => json_encode(['recordeditor' => $recordData]),
    ]);

    expect($this->recordEditorWidget->initialize())->toBeNull();
});

it('prepares vars correctly', function() {
    $this->recordEditorWidget->prepareVars();

    expect($this->recordEditorWidget->vars)
        ->toHaveKey('field')
        ->toHaveKey('addonLeft')
        ->toHaveKey('addonRight')
        ->toHaveKey('addLabel')
        ->toHaveKey('editLabel')
        ->toHaveKey('deleteLabel')
        ->toHaveKey('attachLabel')
        ->toHaveKey('showEditButton')
        ->toHaveKey('showDeleteButton')
        ->toHaveKey('showCreateButton')
        ->toHaveKey('showAttachButton');
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('formwidgets/repeater.js', 'repeater-js');
    Assets::shouldReceive('addCss')->once()->with('formwidgets/recordeditor.css', 'recordeditor-css');
    Assets::shouldReceive('addJs')->once()->with('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');

    $this->recordEditorWidget->assetPath = [];

    $this->recordEditorWidget->loadAssets();
});

it('renders correctly', function() {
    request()->headers->add([
        'X-IGNITER-RECORD-EDITOR-REQUEST-DATA' => json_encode(['recordData' => []]),
    ]);
    $this->recordEditorWidget->config['modelClass'] = RecordEditorLanguageCustomMethod::class;
    $this->recordEditorWidget->addonLeft = 'icon-plus';

    $this->recordEditorWidget->initialize();

    expect($this->recordEditorWidget->render())->toBeString();
});

it('loads new record correctly', function() {
    expect($this->recordEditorWidget->onLoadRecord())->toBeString();
});

it('loads existing record correctly', function() {
    $language = Language::factory()->create();
    request()->request->add(['recordId' => $language->getKey()]);

    expect($this->recordEditorWidget->onLoadRecord())->toBeString();
});

it('creates record correctly', function() {
    $recordData = [
        'name' => 'Test Language',
        'code' => 'test-language',
        'idiom' => 'te',
        'status' => true,
    ];
    request()->request->add(['language' => ['recordData' => $recordData]]);

    expect($this->recordEditorWidget->onSaveRecord())->toBeArray();
    $this->assertDatabaseHas('languages', $recordData);
});

it('updates record correctly', function() {
    $language = Language::factory()->create();
    $recordData = [
        'name' => 'Test Language',
        'code' => 'test-language',
        'idiom' => 'te',
        'status' => true,
    ];
    request()->request->add(['recordId' => $language->getKey()]);
    request()->request->add(['language' => ['recordData' => $recordData]]);

    expect($this->recordEditorWidget->onSaveRecord())->toBeArray();
    $this->assertDatabaseHas('languages', $recordData);
});

it('onDeleteRecord throws exception when record ID is missing', function() {
    expect(fn() => $this->recordEditorWidget->onDeleteRecord())
        ->toThrow(FlashException::class, lang('igniter::admin.form.missing_id'));
});

it('onDeleteRecord throws exception when record is not found', function() {
    request()->request->add(['recordId' => 123]);

    $this->expectException(FlashException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.form.record_not_found_in_model'), 123, RecordEditorLanguage::class));

    $this->recordEditorWidget->onDeleteRecord();
});

it('deletes record correctly', function() {
    $language = Language::factory()->create();
    request()->request->add(['recordId' => $language->getKey()]);

    expect($this->recordEditorWidget->onDeleteRecord())->toBeArray();

    $this->assertDatabaseMissing('languages', ['language_id' => $language->getKey()]);
});

it('onAttachRecord throws exception when record ID is missing', function() {
    expect(fn() => $this->recordEditorWidget->onAttachRecord())
        ->toThrow(FlashException::class, 'Please select a record to attach.');
});

it('onAttachRecord throws exception when record is not found', function() {
    request()->request->add(['recordId' => 123]);

    $this->expectException(FlashException::class);
    $this->expectExceptionMessage(sprintf(lang('igniter::admin.form.record_not_found_in_model'), 123, RecordEditorLanguage::class));

    $this->recordEditorWidget->onAttachRecord();
});

it('attaches record correctly', function() {
    $this->recordEditorWidget->bindToController();
    $language = Language::factory()->create();
    request()->request->add(['recordId' => $language->getKey()]);

    $connectorWidget = new Connector($this->controller, $this->formField, [
        'model' => Page::factory()->create(),
    ]);
    $connectorWidget->bindToController();

    expect($this->recordEditorWidget->onAttachRecord())->toBeArray();
});

class RecordEditorLanguage extends Language
{
    protected $table = 'languages';

    public function getRecordEditorOptions()
    {
        return [];
    }

    public function attachRecordTo($model) {}
}

class RecordEditorLanguageCustomMethod extends Model
{
    protected $table = 'languages';

    public function getTestFieldRecordEditorOptions()
    {
        return [];
    }

    public function attachRecordTo($model) {}
}
