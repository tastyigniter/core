<?php

namespace Igniter\Main\FormWidgets;

use Exception;
use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Traits\FormModelWidget;
use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Illuminate\Contracts\Validation\Validator;

/**
 * Template Editor
 */
class TemplateEditor extends BaseFormWidget
{
    use FormModelWidget;
    use ValidatesForm;

    public $form;

    public $placeholder = 'igniter::system.themes.text_select_file';

    public $formName = 'igniter::system.themes.label_template';

    public $addLabel = 'igniter::system.themes.button_new_source';

    public $editLabel = 'igniter::system.themes.button_rename_source';

    public $deleteLabel = 'igniter::system.themes.button_delete_source';

    //
    // Object properties
    //

    protected $defaultAlias = 'templateeditor';

    protected ThemeManager $manager;

    protected $templateConfig = [
        '_pages' => 'igniter::models/main/page',
        '_partials' => 'igniter::models/main/partial',
        '_layouts' => 'igniter::models/main/layout',
        '_content' => 'igniter::models/main/content',
    ];

    /**
     * @var \Igniter\Admin\Classes\BaseWidget|string|null
     */
    protected $templateWidget;

    /**
     * @var string
     */
    protected $templateType;

    /**
     * @var string
     */
    protected $templateFile;

    public function initialize()
    {
        $this->fillFromConfig([
            'form',
            'formName',
            'addLabel',
            'editLabel',
            'deleteLabel',
            'placeholder',
        ]);

        $this->manager = resolve(ThemeManager::class);
        if (!$this->previewMode = $this->manager->isLocked($this->model->code)) {
            $this->templateWidget = $this->makeTemplateFormWidget();
        }
    }

    public function render()
    {
        $this->prepareVars();

        if ($this->templateWidget) {
            $this->setTemplateValue('mTime', $this->getTemplateModifiedTime());
        }

        return $this->makePartial('templateeditor/templateeditor');
    }

    public function prepareVars()
    {
        $this->vars['field'] = $this->formField;
        $this->vars['fieldOptions'] = $this->getTemplateEditorOptions();
        $this->vars['templateTypes'] = $templateTypes = $this->getTemplateTypes();

        $this->vars['selectedTemplateType'] = $templateType = $this->getTemplateType();
        $this->vars['selectedTemplateFile'] = $this->getTemplateFile();
        $this->vars['selectedTypeLabel'] = str_singular(lang($templateTypes[$templateType]));

        $this->vars['templateWidget'] = $this->templateWidget;
        $this->vars['templatePrimaryTabs'] = optional($this->templateWidget)->getTab('outside');
        $this->vars['templateSecondaryTabs'] = optional($this->templateWidget)->getTab('primary');
    }

    /**
     * Reloads the widgets primary contents.
     */
    public function reload(): array
    {
        $this->templateWidget = $this->makeTemplateFormWidget();
        $this->prepareVars();

        return [
            '#notification' => $this->makePartial('flash'),
            '#'.$this->getId('container') => $this->makePartial('templateeditor/container'),
        ];
    }

    public function onChooseFile()
    {
        $this->validate(post('Theme.source.template'), [
            'type' => ['required', 'in:_pages,_partials,_layouts,_content'],
            'file' => ['sometimes', 'nullable', 'string'],
        ], [], [
            'type' => 'Source Type',
            'file' => 'Source File',
        ]);

        $this->setTemplateValue('type', post('Theme.source.template.type'));
        $this->setTemplateValue('file', post('Theme.source.template.file'));

        return $this->controller->refresh();
    }

    public function onManageSource()
    {
        if ($this->manager->isLocked($this->model->code)) {
            throw new ApplicationException(lang('igniter::system.themes.alert_theme_locked'));
        }

        $data = $this->validate(post(), [
            'action' => ['required', 'in:delete,rename,new'],
            'name' => ['present', 'regex:/^[a-zA-Z-_\/]+$/'],
        ], [], [
            'action' => 'Source Action',
            'name' => 'Source Name',
        ]);

        $fileAction = array_get($data, 'action');
        $newFileName = sprintf('%s/%s', $this->getTemplateType(), array_get($data, 'name'));
        $fileName = $this->getFilename();

        if ($fileAction == 'rename') {
            $this->manager->renameFile($fileName, $newFileName, $this->model->code);
            flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Template file renamed '));
        } elseif ($fileAction == 'delete') {
            $this->manager->deleteFile($fileName, $this->model->code);
            flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Template file deleted '));
        } else {
            $this->manager->newFile($newFileName, $this->model->code);
            flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Template file created '));
        }

        $this->setTemplateValue('file', array_get($data, 'name'));

        return $this->controller->refresh();
    }

    public function onSaveSource()
    {
        if ($this->manager->isLocked($this->model->code)) {
            throw new ApplicationException(lang('igniter::system.themes.alert_theme_locked'));
        }

        if (!$this->templateWidget) {
            return;
        }

        $data = post('Theme.source');

        $this->validateAfter(function (Validator $validator) {
            if ($this->wasTemplateModified()) {
                $validator->errors()->add('markup', lang('igniter::system.themes.alert_changes_confirm'));
            }
        });

        $this->validate($data,
            array_get($this->templateWidget->config ?? [], 'rules', []),
            array_get($this->templateWidget->config ?? [], 'validationMessages', []),
            array_get($this->templateWidget->config ?? [], 'validationAttributes', [])
        );

        $formData = $this->getTemplateAttributes();

        return $this->templateWidget->data->fileSource->fill($formData)->save();
    }

    protected function makeTemplateFormWidget()
    {
        try {
            $template = $this->manager->readFile($this->getFilename(), $this->model->code);
        } catch (Exception) {
            return null;
        }

        $configFile = $this->templateConfig[$this->getTemplateType()];
        $widgetConfig = $this->loadConfig($configFile, ['form'], 'form');

        $widgetConfig['data'] = [
            'fileName' => $template->getFileName(),
            'baseFileName' => $template->getBaseFileName(),
            'settings' => $template->settings,
            'markup' => $template->getMarkup(),
            'codeSection' => $template->getCode(),
            'fileSource' => $template,
        ];

        $widgetConfig['model'] = $this->model;
        $widgetConfig['arrayName'] = $this->formField->arrayName;
        $widgetConfig['context'] = 'edit';
        $widget = $this->makeWidget(Form::class, $widgetConfig);
        $widget->bindToController();

        return $widget;
    }

    protected function getTemplateEditorOptions()
    {
        if (!($themeObject = $this->model->getTheme()) || !$themeObject instanceof Theme) {
            throw new ApplicationException('Missing theme object on '.get_class($this->model));
        }

        /** @var \Igniter\Flame\Pagic\Model $templateClass */
        $templateClass = $themeObject->getTemplateClass($this->getTemplateType());

        return $templateClass::getDropdownOptions($themeObject->getName(), true);
    }

    protected function getTemplateTypes()
    {
        return [
            '_pages' => 'igniter::system.themes.label_type_page',
            '_partials' => 'igniter::system.themes.label_type_partial',
            '_layouts' => 'igniter::system.themes.label_type_layout',
            '_content' => 'igniter::system.themes.label_type_content',
        ];
    }

    protected function getTemplateAttributes()
    {
        $formData = $this->templateWidget->getSaveData();

        $code = array_get($formData, 'codeSection');
        $code = preg_replace('/^\<\?php/', '', $code);
        $code = preg_replace('/^\<\?/', '', preg_replace('/\?>$/', '', $code));

        $result['code'] = trim($code, PHP_EOL) ?: null;
        $result['markup'] = array_get($formData, 'markup') ?: null;

        $settings = array_get($formData, 'settings', []);

        return array_merge(array_filter($settings), $result);
    }

    protected function wasTemplateModified()
    {
        $sessionTime = $this->getTemplateValue('mTime');
        $mTime = $this->getTemplateModifiedTime();

        return $sessionTime != $mTime;
    }

    protected function getTemplateModifiedTime()
    {
        if (!$this->templateWidget) {
            return null;
        }

        return optional($this->templateWidget->data)->fileSource->mTime;
    }

    public function getTemplateValue($name, $default = null)
    {
        return $this->getSession($this->model->code.'-selected-'.$name, $default);
    }

    public function setTemplateValue($name, $value)
    {
        $this->putSession($this->model->code.'-selected-'.$name, $value);
    }

    public function getTemplateType()
    {
        return $this->getTemplateValue('type') ?? '_pages';
    }

    public function getTemplateFile()
    {
        return $this->getTemplateValue('file');
    }

    protected function getFilename()
    {
        return sprintf('%s/%s', $this->getTemplateType(), $this->getTemplateFile());
    }
}
