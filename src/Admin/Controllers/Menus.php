<?php

namespace Igniter\Admin\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;

class Menus extends AdminController
{
    public $implement = [
        \Igniter\Admin\Controllers\Actions\ListController::class,
        \Igniter\Admin\Controllers\Actions\FormController::class,
        \Igniter\Admin\Controllers\Actions\LocationAwareController::class,
    ];

    public $listConfig = [
        'list' => [
            'model' => \Igniter\Admin\Models\Menu::class,
            'title' => 'lang:igniter::admin.menus.text_title',
            'emptyMessage' => 'lang:igniter::admin.menus.text_empty',
            'defaultSort' => ['menu_id', 'DESC'],
            'configFile' => 'menu',
        ],
    ];

    public $formConfig = [
        'name' => 'lang:igniter::admin.menus.text_form_name',
        'model' => \Igniter\Admin\Models\Menu::class,
        'request' => \Igniter\Admin\Requests\Menu::class,
        'create' => [
            'title' => 'lang:igniter::admin.form.create_title',
            'redirect' => 'menus/edit/{menu_id}',
            'redirectClose' => 'menus',
            'redirectNew' => 'menus/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::admin.form.edit_title',
            'redirect' => 'menus/edit/{menu_id}',
            'redirectClose' => 'menus',
            'redirectNew' => 'menus/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::admin.form.preview_title',
            'redirect' => 'menus',
        ],
        'delete' => [
            'redirect' => 'menus',
        ],
        'configFile' => 'menu',
    ];

    protected $requiredPermissions = 'Admin.Menus';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('menus', 'restaurant');
    }
}
