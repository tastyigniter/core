<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Classes\ListColumn;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\System\Models\Currency;

class Currencies extends \Igniter\Admin\Classes\AdminController
{
    public array $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\Currency::class,
            'title' => 'lang:igniter::system.currencies.text_title',
            'emptyMessage' => 'lang:igniter::system.currencies.text_empty',
            'defaultSort' => ['currency_status', 'DESC'],
            'configFile' => 'currency',
            'back' => 'settings',
        ],
    ];

    public array $formConfig = [
        'name' => 'lang:igniter::system.currencies.text_form_name',
        'model' => \Igniter\System\Models\Currency::class,
        'request' => \Igniter\System\Http\Requests\CurrencyRequest::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'currencies/edit/{currency_id}',
            'redirectClose' => 'currencies',
            'redirectNew' => 'currencies/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'currencies/edit/{currency_id}',
            'redirectClose' => 'currencies',
            'redirectNew' => 'currencies/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'back' => 'currencies',
        ],
        'delete' => [
            'redirect' => 'currencies',
        ],
        'configFile' => 'currency',
    ];

    protected null|string|array $requiredPermissions = 'Site.Currencies';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('settings', 'system');
    }

    public function index_onSetDefault(?string $context)
    {
        $data = $this->validate(post(), [
            'default' => 'required|integer|exists:'.Currency::class.',currency_id',
        ]);

        if (Currency::updateDefault($data['default'])) {
            flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::system.currencies.alert_set_default')));
        }

        return $this->refreshList('list');
    }

    public function listOverrideColumnValue(Currency $record, ListColumn $column, ?string $alias = null)
    {
        if ($column->type == 'button' && $column->columnName == 'default') {
            $column->iconCssClass = $record->isDefault() ? 'fa fa-star' : 'fa fa-star-o';
        }
    }
}
