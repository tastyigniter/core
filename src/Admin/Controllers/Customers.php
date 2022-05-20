<?php

namespace Igniter\Admin\Controllers;

use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Main\Facades\Auth;

class Customers extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Controllers\Actions\ListController::class,
        \Igniter\Admin\Controllers\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\Customer::class,
            'title' => 'lang:igniter::admin.customers.text_title',
            'emptyMessage' => 'lang:igniter::admin.customers.text_empty',
            'defaultSort' => ['customer_id', 'DESC'],
            'configFile' => 'customer',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.customers.text_form_name',
        'model' => \Igniter\Admin\Models\Customer::class,
        'request' => \Igniter\Admin\Requests\Customer::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'customers/edit/{customer_id}',
            'redirectClose' => 'customers',
            'redirectNew' => 'customers/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'customers/edit/{customer_id}',
            'redirectClose' => 'customers',
            'redirectNew' => 'customers/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'customers',
        ],
        'delete' => [
            'redirect' => 'customers',
        ],
        'configFile' => 'customer',
    ];

    protected $requiredPermissions = 'Admin.Customers';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('customers', 'users');
    }

    public function onImpersonate($context, $recordId = null)
    {
        if (!AdminAuth::user()->hasPermission('Admin.ImpersonateCustomers')) {
            throw new ApplicationException(lang('igniter::admin.customers.alert_login_restricted'));
        }

        $id = post('recordId', $recordId);
        if ($customer = $this->formFindModelObject((int)$id)) {
            Auth::stopImpersonate();
            Auth::impersonate($customer);
            flash()->success(sprintf(lang('igniter::admin.customers.alert_impersonate_success'), $customer->full_name));
        }
    }

    public function edit_onActivate($context, $recordId = null)
    {
        if ($customer = $this->formFindModelObject((int)$recordId)) {
            $customer->completeActivation($customer->getActivationCode());
            flash()->success(sprintf(lang('igniter::admin.customers.alert_activation_success'), $customer->full_name));
        }

        return $this->redirectBack();
    }

    public function formExtendModel($model)
    {
        if ($model->exists && !$model->is_activated) {
            Template::setButton(lang('igniter::admin.customers.button_activate'), [
                'class' => 'btn btn-success pull-right',
                'data-request' => 'onActivate',
            ]);
        }
    }

    public function formAfterSave($model)
    {
        if (!$model->group || $model->group->requiresApproval())
            return;

        if ($this->status && !$this->is_activated)
            $model->completeActivation($model->getActivationCode());
    }
}
