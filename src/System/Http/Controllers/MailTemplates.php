<?php

declare(strict_types=1);

namespace Igniter\System\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Database\Model;
use Igniter\System\Classes\MailManager;
use Igniter\System\Http\Controllers\Concerns\HasMailBuilder;
use Igniter\System\Http\Requests\MailTemplateRequest;
use Igniter\System\Models\MailTemplate;

class MailTemplates extends AdminController
{
    use HasMailBuilder;

    protected string $mailBuilderUrl = 'mail_templates';

    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => MailTemplate::class,
            'title' => 'lang:igniter::system.mail_templates.text_template_title',
            'emptyMessage' => 'lang:igniter::system.mail_templates.text_empty',
            'defaultSort' => ['template_id', 'DESC'],
            'configFile' => 'mailtemplate',
        ],
    ];

    public array $formConfig = [
        'name' => 'lang:igniter::system.mail_templates.text_form_name',
        'model' => MailTemplate::class,
        'request' => MailTemplateRequest::class,
        'create' => [
            'title' => 'lang:igniter::system.mail_templates.text_new_template_title',
            'redirect' => 'mail_templates/builder/{template_id}',
            'redirectClose' => 'mail_templates',
            'redirectNew' => 'mail_templates/create',
        ],
        'edit' => [
            'title' => 'lang:igniter::system.mail_templates.text_edit_template_title',
            'redirect' => 'mail_templates/edit/{template_id}',
            'redirectClose' => 'mail_templates',
            'redirectNew' => 'mail_templates/create',
        ],
        'preview' => [
            'title' => 'lang:igniter::system.mail_templates.text_preview_template_title',
            'back' => 'mail_templates',
        ],
        'delete' => [
            'redirect' => 'mail_templates',
        ],
        'configFile' => 'mailtemplate',
    ];

    protected null|string|array $requiredPermissions = 'Admin.MailTemplates';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('mail_templates', 'design');
    }

    public function index(): void
    {
        MailTemplate::syncAll();

        $this->asExtension('ListController')->index();
    }

    public function formExtendFields(Form $form): void
    {
        if ($form->context != 'create' && $field = $form->getField('code')) {
            $field->disabled = true;
        }
    }

    public function formBeforeSave(MailTemplate $model): void
    {
        $model->setAttribute('is_custom', true);
    }

    protected function mailBuilderFields(): array
    {
        return [
            // Edited in the subject bar of the builder canvas
            'subject' => [
                'type' => 'hidden',
            ],
            'blocks' => [
                'type' => 'mailbuilder',
                'mode' => 'template',
            ],
        ];
    }

    protected function mailBuilderRules(): array
    {
        return ['subject' => ['required', 'string', 'max:255']];
    }

    protected function mailBuilderBeforeSave(Model $model): void
    {
        $model->setAttribute('is_custom', true);
    }

    protected function mailBuilderIsRegistered(Model $model): bool
    {
        return array_key_exists((string)$model->getAttribute('code'), (array)resolve(MailManager::class)->listRegisteredTemplates());
    }

    protected function mailBuilderResync(): void
    {
        MailTemplate::syncAll();
    }
}
