<?php

namespace Igniter\Admin\FormWidgets;

use Exception;
use Igniter\Admin\ActivityTypes\AssigneeUpdated;
use Igniter\Admin\ActivityTypes\StatusUpdated;
use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Classes\FormField;
use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Models\Order;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Models\User;
use Igniter\Admin\Models\UserGroup;
use Igniter\Admin\Traits\FormModelWidget;
use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\ValidationException;

/**
 * Status Editor
 */
class StatusEditor extends BaseFormWidget
{
    use FormModelWidget;
    use ValidatesForm;

    /**
     * @var Order|\Igniter\Admin\Models\Reservation Form model object.
     */
    public $model;

    public $form;

    public $formTitle = 'igniter::admin.statuses.text_editor_title';

    /**
     * @var string Text to display for the title of the popup list form
     */
    public $statusFormName = 'Status';

    public $statusArrayName = 'statusData';

    /**
     * @var string Relation column to display for the name
     */
    public $statusKeyFrom = 'status_id';

    /**
     * @var string Relation column to display for the name
     */
    public $statusNameFrom = 'status_name';

    /**
     * @var string Relation column to display for the color
     */
    public $statusColorFrom = 'status_color';

    public $statusRelationFrom = 'status';

    public $statusModelClass = \Igniter\Admin\Models\StatusHistory::class;

    /**
     * @var string Text to display for the title of the popup list form
     */
    public $assigneeFormName = 'Assignee';

    public $assigneeArrayName = 'assigneeData';

    public $assigneeKeyFrom = 'assignee_id';

    public $assigneeGroupKeyFrom = 'assignee_group_id';

    /**
     * @var string Relation column to display for the name
     */
    public $assigneeNameFrom = 'name';

    public $assigneeGroupNameFrom = 'user_group_name';

    public $assigneeRelationFrom = 'assignee';

    public $assigneeModelClass = \Igniter\Admin\Models\AssignableLog::class;

    public $assigneeOrderPermission = 'Admin.AssignOrders';

    public $assigneeReservationPermission = 'Admin.AssignReservations';

    //
    // Object properties
    //

    protected $defaultAlias = 'statuseditor';

    protected $modelClass;

    protected $isStatusMode;

    public function initialize()
    {
        $this->fillFromConfig([
            'form',
            'statusArrayName',
            'statusKeyFrom',
            'statusFormName',
            'statusRelationFrom',
            'statusNameFrom',
            'statusColorFrom',
            'assigneeKeyFrom',
            'assigneeFormName',
            'assigneeArrayName',
            'assigneeRelationFrom',
            'assigneeNameFrom',
            'assigneeOrderPermission',
            'assigneeReservationPermission',
        ]);
    }

    public function render()
    {
        $this->setMode('status');
        $this->prepareVars();

        return $this->makePartial('statuseditor/statuseditor');
    }

    public function onLoadRecord()
    {
        $context = post('recordId');
        if (!in_array($context, ['load-status', 'load-assignee']))
            throw new ApplicationException(lang('igniter::admin.statuses.alert_invalid_action'));

        $this->setMode(str_after($context, 'load-'));

        $formTitle = sprintf(lang($this->formTitle), lang($this->getModeConfig('formName')));
        $model = $this->createFormModel();

        return $this->makePartial('statuseditor/form', [
            'formTitle' => $formTitle,
            'formWidget' => $this->makeEditorFormWidget($model),
        ]);
    }

    public function onSaveRecord()
    {
        $this->setMode($context = post('context'));

        $keyFrom = $this->getModeConfig('keyFrom');
        $arrayName = $this->getModeConfig('arrayName');
        $recordId = post($arrayName.'.'.$keyFrom);

        if (!$this->isStatusMode)
            $this->checkAssigneePermission();

        $model = $this->createFormModel();
        $form = $this->makeEditorFormWidget($model);
        $saveData = $this->mergeSaveData($form->getSaveData());

        try {
            if ($this->isStatusMode && $recordId == $this->model->{$keyFrom})
                throw new ApplicationException(sprintf(lang('igniter::admin.statuses.alert_already_added'), $context, $context));

            $this->validateFormWidget($form, $saveData);
        }
        catch (ValidationException $ex) {
            throw new ApplicationException($ex->getMessage());
        }

        if ($this->saveRecord($saveData, $keyFrom)) {
            flash()->success(sprintf(lang('igniter::admin.alert_success'), lang($this->getModeConfig('formName')).' '.'updated'))->now();
        }
        else {
            flash()->error(lang('igniter::admin.alert_error_try_again'))->now();
        }

        $this->prepareVars();

        return [
            '#notification' => $this->makePartial('flash'),
            '#'.$this->getId() => $this->makePartial('statuseditor/info'),
        ];
    }

    public function onLoadStatus()
    {
        if (!strlen($statusId = post('statusId')))
            throw new ApplicationException(lang('igniter::admin.form.missing_id'));

        if (!$status = Status::find($statusId))
            throw new Exception(sprintf(lang('igniter::admin.statuses.alert_status_not_found'), $statusId));

        return $status->toArray();
    }

    public function onLoadAssigneeList()
    {
        if (!strlen($groupId = post('groupId')))
            throw new ApplicationException(lang('igniter::admin.form.missing_id'));

        $this->setMode('assignee');

        $model = $this->createFormModel();

        $form = $this->makeEditorFormWidget($model);

        $formField = $form->getField($this->assigneeKeyFrom);

        return [
            '#'.$formField->getId() => $form->renderField($formField, [
                'useContainer' => false,
            ]),
        ];
    }

    public function loadAssets()
    {
        $this->addJs('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
        $this->addJs('statuseditor.js', 'statuseditor-js');
    }

    public function prepareVars()
    {
        $this->vars['field'] = $this->formField;
        $this->vars['status'] = $this->model->{$this->statusRelationFrom};
        $this->vars['assignee'] = $this->model->{$this->assigneeRelationFrom};
    }

    public function getSaveValue($value)
    {
        return FormField::NO_SAVE_DATA;
    }

    public static function getAssigneeOptions($form, $field)
    {
        if (!strlen($groupId = post('groupId', $form->getField('assignee_group_id')->value)))
            return [];

        return User::whereHas('groups', function ($query) use ($groupId) {
            $query->where('user_groups.user_group_id', $groupId);
        })->isEnabled()->dropdown('name');
    }

    public static function getAssigneeGroupOptions()
    {
        if (AdminAuth::isSuperUser()) {
            return UserGroup::getDropdownOptions();
        }

        return AdminAuth::user()->groups->pluck('user_group_name', 'user_group_id');
    }

    protected function makeEditorFormWidget($model)
    {
        $widgetConfig = is_string($this->form)
            ? $this->loadConfig($this->form, ['form'], 'form') : $this->form;

        $widgetConfig['model'] = $model;
        $widgetConfig['alias'] = $this->alias.'StatusEditor';
        $widgetConfig['context'] = $this->isStatusMode ? 'status' : 'assignee';
        $widgetConfig['arrayName'] = $this->getModeConfig('arrayName');
        $widget = $this->makeWidget(Form::class, $widgetConfig);

        $widget->bindEvent('form.extendFieldsBefore', function () use ($widget) {
            $this->formExtendFieldsBefore($widget);
        });

        $widget->bindEvent('form.extendFields', function ($fields) use ($widget) {
            $this->formExtendFields($widget, $fields);
        });

        $widget->bindToController();

        return $widget;
    }

    protected function setMode($context)
    {
        $this->isStatusMode = $context !== 'assignee';
        $this->modelClass = $this->isStatusMode
            ? $this->statusModelClass : $this->assigneeModelClass;
    }

    protected function getModeConfig($key)
    {
        $key = ucfirst($key);

        return $this->isStatusMode ? $this->{'status'.$key} : $this->{'assignee'.$key};
    }

    protected function mergeSaveData()
    {
        return array_merge(post($this->getModeConfig('arrayName'), []), [
            'user_id' => $this->getController()->getUser()->getKey(),
        ]);
    }

    protected function formExtendFieldsBefore($form)
    {
        if ($this->isStatusMode) {
            if ($status = $this->model->{$this->statusRelationFrom}) {
                $form->fields['status_id']['default'] = $status->getKey();
                $form->fields['comment']['default'] = $status->status_comment;
                $form->fields['notify']['default'] = $status->notify_customer;
            }

            return;
        }

        $form->fields['assignee_group_id']['default'] = $this->model->assignee_group_id;
        $form->fields['assignee_id']['default'] = $this->model->assignee_id;
        $form->fields['assignee_group_id']['options'] = [$this, 'getAssigneeGroupOptions'];
        $form->fields['assignee_id']['options'] = [$this, 'getAssigneeOptions'];
    }

    protected function formExtendFields($form, $fields)
    {
    }

    protected function checkAssigneePermission()
    {
        $saleType = $this->model instanceof Order
            ? 'orderPermission' : 'reservationPermission';

        $permission = $this->getModeConfig($saleType);

        if (!$this->controller->getUser()->hasPermission($permission))
            throw new ApplicationException(lang('igniter::admin.alert_user_restricted'));
    }

    protected function saveRecord(array $saveData, string $keyFrom)
    {
        if (!$this->isStatusMode) {
            $group = UserGroup::find(array_get($saveData, $this->assigneeGroupKeyFrom));
            $user = User::find(array_get($saveData, $keyFrom));
            if ($record = $this->model->updateAssignTo($group, $user))
                AssigneeUpdated::log($record, $this->getController()->getUser());
        }
        else {
            $status = Status::find(array_get($saveData, $keyFrom));
            if ($record = $this->model->addStatusHistory($status, $saveData))
                StatusUpdated::log($record, $this->getController()->getUser());
        }

        return $record;
    }
}
