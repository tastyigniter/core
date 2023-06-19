<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;
use Igniter\System\Models\Country;

/**
 * Controller Class Countries
 */
class Countries extends \Igniter\Admin\Classes\AdminController
{
    public $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\Country::class,
            'title' => 'lang:igniter::system.countries.text_title',
            'emptyMessage' => 'lang:igniter::system.countries.text_empty',
            'defaultSort' => ['country_name', 'ASC'],
            'configFile' => 'country',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::system.countries.text_form_name',
        'model' => \Igniter\System\Models\Country::class,
        'request' => \Igniter\System\Requests\CountryRequest::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'countries/edit/{country_id}',
            'redirectClose' => 'countries',
            'redirectNew' => 'countries/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'countries/edit/{country_id}',
            'redirectClose' => 'countries',
            'redirectNew' => 'countries/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'countries',
        ],
        'delete' => [
            'redirect' => 'countries',
        ],
        'configFile' => 'country',
    ];

    protected $requiredPermissions = 'Site.Countries';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('settings', 'system');
    }

    public function index()
    {
        rescue(function () {
            if (!Country::count()) {
                Country::upsertFromHub();
            }
        });

        $this->asExtension('ListController')->index();
    }

    public function index_onSetDefault($context = null)
    {
        if (Country::updateDefault(post('default'))) {
            flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::system.countries.alert_set_default')));
        }

        return $this->refreshList('list');
    }

    public function listOverrideColumnValue($record, $column, $alias = null)
    {
        if ($column->type == 'button' && $column->columnName == 'default') {
            $column->iconCssClass = $record->isDefault() ? 'fa fa-star' : 'fa fa-star-o';
        }
    }
}
