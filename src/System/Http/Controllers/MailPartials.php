<?php

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Widgets\Form;
use Igniter\System\Models\MailPartial;

class MailPartials extends \Igniter\Admin\Classes\AdminController
{
    public array $implement = [
        \Igniter\Admin\Http\Actions\ListController::class,
        \Igniter\Admin\Http\Actions\FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => \Igniter\System\Models\MailPartial::class,
            'title' => 'lang:igniter::system.mail_templates.text_partial_title',
            'emptyMessage' => 'lang:igniter::system.mail_templates.text_empty',
            'defaultSort' => ['partial_id', 'DESC'],
            'configFile' => 'mailpartial',
            'back' => 'mail_templates',
        ],
    ];

    public array $formConfig = [
        'name' => 'lang:igniter::system.mail_templates.text_partial_form_name',
        'model' => \Igniter\System\Models\MailPartial::class,
        'request' => \Igniter\System\Http\Requests\MailPartialRequest::class,
        'create' => [
            'title' => 'lang:igniter::system.mail_templates.text_new_partial_title',
            'redirect' => 'mail_partials/edit/{partial_id}',
            'redirectClose' => 'mail_partials',
            'redirectNew' => 'mail_partials/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::system.mail_templates.text_edit_partial_title',
            'redirect' => 'mail_partials/edit/{partial_id}',
            'redirectClose' => 'mail_partials',
            'redirectNew' => 'mail_partials/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::system.mail_templates.text_preview_partial_title',
            'back' => 'mail_partials',
        ],
        'delete' => [
            'redirect' => 'mail_partials',
        ],
        'configFile' => 'mailpartial',
    ];

    protected null|string|array $requiredPermissions = 'Admin.MailTemplates';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('mail_templates', 'design');
    }

    public function formExtendFields(Form $form)
    {
        if ($form->context != 'create') {
            $field = $form->getField('code');
            $field->disabled = true;
        }
    }

    public function formBeforeSave(MailPartial $model)
    {
        $model->is_custom = true;
    }
}
