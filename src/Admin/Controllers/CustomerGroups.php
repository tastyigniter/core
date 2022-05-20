<?php

namespace Igniter\Admin\Controllers;

use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Models\CustomerGroup;

class CustomerGroups extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Controllers\Actions\ListController::class,
        \Igniter\Admin\Controllers\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\CustomerGroup::class,
            'title' => 'lang:igniter::admin.customer_groups.text_title',
            'emptyMessage' => 'lang:igniter::admin.customer_groups.text_empty',
            'defaultSort' => ['customer_group_id', 'DESC'],
            'configFile' => 'customergroup',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.customer_groups.text_form_name',
        'model' => \Igniter\Admin\Models\CustomerGroup::class,
        'request' => \Igniter\Admin\Requests\CustomerGroup::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'customer_groups/edit/{customer_group_id}',
            'redirectClose' => 'customer_groups',
            'redirectNew' => 'customer_groups/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'customer_groups/edit/{customer_group_id}',
            'redirectClose' => 'customer_groups',
            'redirectNew' => 'customer_groups/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'customer_groups',
        ],
        'delete' => [
            'redirect' => 'customer_groups',
        ],
        'configFile' => 'customergroup',
    ];

    protected $requiredPermissions = 'Admin.CustomerGroups';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('customers', 'users');
    }

    public function index_onSetDefault()
    {
        if (CustomerGroup::updateDefault(post('default'))) {
            flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::admin.customer_groups.alert_set_default')));
        }

        return $this->refreshList('list');
    }

    public function listOverrideColumnValue($record, $column, $alias = null)
    {
        if ($column->type != 'button')
            return null;

        if ($column->columnName != 'default')
            return null;

        $attributes = $column->attributes;
        $column->iconCssClass = 'fa fa-star-o';
        if ($record->getKey() == setting('customer_group_id')) {
            $column->iconCssClass = 'fa fa-star';
        }

        return $attributes;
    }
}
