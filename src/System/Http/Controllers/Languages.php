<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Classes\ListColumn;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\FlashException;
use Igniter\System\Classes\LanguageManager;
use Igniter\System\Models\Language;
use Igniter\System\Traits\ManagesUpdates;
use Igniter\System\Traits\SessionMaker;

class Languages extends \Igniter\Admin\Classes\AdminController
{
    use ManagesUpdates;
    use SessionMaker;

    public array $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\Language::class,
            'title' => 'lang:igniter::system.languages.text_title',
            'emptyMessage' => 'lang:igniter::system.languages.text_empty',
            'defaultSort' => ['language_id', 'DESC'],
            'configFile' => 'language',
            'back' => 'settings',
        ],
    ];

    public array $formConfig = [
        'name' => 'lang:igniter::system.languages.text_form_name',
        'model' => \Igniter\System\Models\Language::class,
        'request' => \Igniter\System\Http\Requests\LanguageRequest::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'languages/edit/{language_id}',
            'redirectClose' => 'languages',
            'redirectNew' => 'languages/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'languages/edit/{language_id}',
            'redirectClose' => 'languages',
            'redirectNew' => 'languages/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'back' => 'languages',
        ],
        'delete' => [
            'redirect' => 'languages',
        ],
        'configFile' => 'language',
    ];

    protected null|string|array $requiredPermissions = 'Site.Languages';

    protected ?array $localeFiles = null;

    protected int $totalStrings = 0;

    protected int $totalTranslated = 0;

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('settings', 'system');
    }

    public function index()
    {
        Language::applySupportedLanguages();

        $this->initUpdate('language');

        $this->asExtension('ListController')->index();
    }

    public function search()
    {
        $filter = input('filter');
        if (!$filter || !is_array($filter) || !isset($filter['search']) || !strlen($filter['search'])) {
            return [];
        }

        return resolve(LanguageManager::class)->searchLanguages($filter['search']);
    }

    public function edit(?string $context = null, ?string $recordId = null)
    {
        $this->addJs('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
        $this->addJs('formwidgets/translationseditor.js', 'translationseditor-js');

        $this->prepareAssets();

        $this->asExtension('FormController')->edit($context, $recordId);
    }

    public function index_onSetDefault(?string $context = null)
    {
        $data = $this->validate(post(), [
            'default' => 'required|string|exists:'.Language::class.',code',
        ]);

        if (Language::updateDefault($data['default'])) {
            flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::system.languages.alert_set_default')));
        }

        return $this->refreshList('list');
    }

    public function listOverrideColumnValue(Language $record, ListColumn $column, ?string $alias = null)
    {
        if ($column->type == 'button' && $column->columnName == 'default') {
            $column->iconCssClass = $record->isDefault() ? 'fa fa-star' : 'fa fa-star-o';
        }
    }

    public function edit_onSubmitFilter(?string $context = null, ?string $recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        $this->asExtension('FormController')->initForm($model, $context);

        $group = post('Language._group');
        $this->setFilterValue('group', !strlen($group) ? null : $group);

        $term = post('Language._search');
        $this->setFilterValue('search', (!is_string($term) || !strlen($term)) ? null : $term);

        $filter = post('Language._filter');
        $this->setFilterValue('filter', (!strlen($filter) || !is_string($filter)) ? null : $filter);

        return $this->asExtension('FormController')->makeRedirect('edit', $model);
    }

    public function edit_onCheckUpdates(?string $context = null, ?string $recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        $response = resolve(LanguageManager::class)->applyLanguagePack($model->code, (array)$model->version);

        return $this->makePartial('updates', [
            'locale' => $model->code,
            'updates' => $response,
        ]);
    }

    public function edit_onPublishTranslations(?string $context = null, ?string $recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        resolve(LanguageManager::class)->publishTranslations($model);

        flash()->success(lang('igniter::system.languages.alert_publish_success'));

        return $this->asExtension('FormController')->makeRedirect('edit', $model);
    }

    public function onApplyItems()
    {
        $items = post('items') ?? [];
        if (!count($items)) {
            throw new FlashException(lang('igniter::system.updates.alert_no_items'));
        }

        $this->validateItems();

        $itemMeta = $items[0];

        throw_unless(
            $response = resolve(LanguageManager::class)->findLanguage($itemMeta['name']),
            new FlashException(lang('igniter::system.languages.alert_language_not_found'))
        );

        if (!Language::findByCode($itemMeta['name'])) {
            $language = Language::make(['code' => $itemMeta['name']]);
            $language->name = $response['name'];
            $language->status = true;
            $language->save();
        }

        $response = resolve(LanguageManager::class)->applyLanguagePack($itemMeta['name']);

        return [
            'steps' => $response ? $this->buildProcessSteps([$response]) : [],
        ];
    }

    public function onApplyUpdate(?string $context = null, ?string $recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        $response = resolve(LanguageManager::class)->applyLanguagePack($model->code, (array)$model->version);

        return [
            'steps' => $this->buildProcessSteps($response),
        ];
    }

    public function onProcessItems(?string $context = null, ?string $recordId = null)
    {
        $model = $this->formFindModelObject($recordId);

        $data = $this->validate(post(), [
            'process' => ['required', 'string'],
            'meta' => ['required', 'array'],
            'meta.code' => ['required', 'string'],
            'meta.name' => ['required', 'string'],
            'meta.author' => ['required', 'string'],
            'meta.type' => ['required', 'in:core,extension,theme'],
            'meta.version' => ['required', 'string'],
            'meta.hash' => ['required', 'string'],
            'meta.description' => ['sometimes', 'string'],
        ], [], [
            'process' => lang('igniter::system.updates.label_meta_step'),
            'meta.code' => lang('igniter::system.updates.label_meta_code'),
            'meta.name' => lang('igniter::system.updates.label_meta_name'),
            'meta.type' => lang('igniter::system.updates.label_meta_type'),
            'meta.author' => lang('igniter::system.updates.label_meta_author'),
            'meta.version' => lang('igniter::system.updates.label_meta_version'),
            'meta.hash' => lang('igniter::system.updates.label_meta_hash'),
            'meta.description' => lang('igniter::system.updates.label_meta_description'),
        ]);

        resolve(LanguageManager::class)->installLanguagePack($model->code, [
            'name' => $data['meta']['code'],
            'type' => $data['meta']['type'],
            'ver' => str_before($data['meta']['version'], '+'),
            'build' => str_after($data['meta']['version'], '+'),
            'hash' => $data['meta']['hash'],
        ]);

        $model->updateVersions($data['meta']);

        return [
            'success' => true,
            'message' => sprintf(lang('igniter::system.languages.alert_update_complete'),
                $model->code, $data['meta']['name']
            ),
        ];
    }

    public function formExtendModel(Model $model)
    {
        if (!$model->exists) {
            return;
        }

        Template::setButton(lang('igniter::system.languages.button_import_translations'), [
            'class' => 'btn btn-light pull-right',
            'data-toggle' => 'record-editor',
            'data-handler' => 'onCheckUpdates',
        ]);
    }

    public function formExtendFields(Form $form, array $fields)
    {
        if ($form->getContext() !== 'edit') {
            return;
        }

        $groupField = $form->getField('_group');
        $searchField = $form->getField('_search');
        $filterField = $form->getField('_filter');
        $field = $form->getField('translations');

        $groupField->value = $this->getFilterValue('group');
        $searchField->value = $this->getFilterValue('search');
        $filterField->value = $this->getFilterValue('filter', 'changed');
        $field->value = $this->getFilterValue('search');

        $field->options = resolve(LanguageManager::class)->listTranslations(
            $form->model, $groupField->value, $filterField->value, $searchField->value
        );
    }

    protected function getFilterValue(string $key, ?string $default = null)
    {
        return $this->getSession('translation_'.$key, $default);
    }

    protected function setFilterValue(string $key, ?string $value = null)
    {
        if (is_null($value)) {
            $this->forgetSession('translation_'.$key);
        } else {
            $this->putSession('translation_'.$key, trim($value));
        }
    }

    protected function buildProcessSteps(array $itemsToUpdate): array
    {
        $processSteps = [];
        foreach ($itemsToUpdate as $item) {
            $step = 'update-'.$item['code'];
            $processSteps[$step] = [
                'meta' => $item,
                'process' => $step,
                'progress' => sprintf(lang('igniter::system.languages.alert_update_progress'), $item['locale'], $item['name']),
            ];
        }

        return $processSteps;
    }
}
