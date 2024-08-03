<?php

namespace Igniter\System\Http\Controllers;

use Exception;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Admin\Traits\FormExtendable;
use Igniter\Admin\Traits\WidgetMaker;
use Igniter\Admin\Widgets\Form;
use Igniter\Admin\Widgets\Toolbar;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Models\MailTemplate;
use Igniter\System\Models\Settings as SettingsModel;
use Igniter\User\Facades\AdminAuth;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class Settings extends \Igniter\Admin\Classes\AdminController
{
    use FormExtendable;
    use WidgetMaker;

    protected null|string|array $requiredPermissions = 'Site.Settings';

    protected $modelClass = SettingsModel::class;

    public ?Form $formWidget = null;

    public ?Toolbar $toolbarWidget = null;

    public ?string $settingCode = null;

    public array $settingItemErrors = [];

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('settings', 'system');
    }

    public function index()
    {
        MailTemplate::syncAll();

        $this->validateSettingItems(true);

        // For security reasons, delete setup files if still exists.
        if (File::isFile(base_path('setup.php')) || File::isDirectory(base_path('setup'))) {
            flash()->danger(lang('igniter::system.settings.alert_delete_setup_files'))->important()->now();
        }

        $pageTitle = lang('igniter::system.settings.text_title');
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);
        $this->vars['settings'] = $this->createModel()->listSettingItems();
        $this->vars['settingItemErrors'] = $this->settingItemErrors;
    }

    public function edit(string $context, ?string $settingCode = null)
    {
        $this->settingCode = $settingCode;
        [$model, $definition] = $this->findSettingDefinitions($settingCode);

        throw_unless($definition, new FlashException(
            sprintf(lang('igniter::system.settings.alert_settings_not_found'), $settingCode)
        ));

        if ($definition->permission && !$this->getUser()->hasPermission($definition->permission)) {
            return Response::make(View::make('admin::access_denied'), 403);
        }

        $pageTitle = sprintf(lang('igniter::system.settings.text_edit_title'), lang($definition->label));
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);
        AdminMenu::setPreviousUrl('settings');

        $this->initWidgets($model, $definition);
    }

    public function edit_onSave(string $context, ?string $settingCode = null)
    {
        $this->settingCode = $settingCode;
        [$model, $definition] = $this->findSettingDefinitions($settingCode);
        throw_unless($definition,
            new FlashException(lang('igniter::system.settings.alert_settings_not_found'))
        );

        if ($definition->permission && !$this->getUser()->hasPermission($definition->permission)) {
            return Response::make(View::make('igniter.admin::access_denied'), 403);
        }

        $this->initWidgets($model, $definition);

        $saveData = $this->formWidget->getSaveData();

        $this->validateFormRequest($definition->request, function(Request $request) use ($saveData) {
            $request->merge($saveData);
        });

        if ($this->formValidate($model, $this->formWidget) === false) {
            return request()->ajax() ? ['#notification' => $this->makePartial('flash')] : false;
        }

        SettingsModel::set($saveData);

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang($definition->label).' settings updated '));

        if (post('close')) {
            return $this->redirect('settings');
        }

        return $this->refresh();
    }

    public function edit_onTestMail()
    {
        [$model, $definition] = $this->findSettingDefinitions('mail');
        throw_unless($definition,
            new FlashException(lang('igniter::system.settings.alert_settings_not_found'))
        );

        $this->initWidgets($model, $definition);

        $saveData = $this->formWidget->getSaveData();

        $this->validateFormRequest($definition->request, function(Request $request) use ($saveData) {
            $request->merge($saveData);
        });

        if ($this->formValidate($model, $this->formWidget) === false) {
            return request()->ajax() ? ['#notification' => $this->makePartial('flash')] : false;
        }

        SettingsModel::set($saveData);

        $name = AdminAuth::getStaffName();
        $email = AdminAuth::getStaffEmail();

        try {
            Mail::raw(lang('igniter::system.settings.text_test_email_message'), function(Message $message) use ($name, $email) {
                $message->to($email, $name)->subject('This a test email');
            });

            flash()->success(sprintf(lang('igniter::system.settings.alert_email_sent'), $email));
        } catch (Exception $ex) {
            flash()->error($ex->getMessage());
        }

        return $this->refresh();
    }

    public function initWidgets(SettingsModel $model, \stdClass $definition)
    {
        $modelConfig = $this->getFieldConfig($definition->code, $model);

        $formConfig = array_except($modelConfig, 'toolbar');
        $formConfig['model'] = $model;
        $formConfig['data'] = array_undot($model->getFieldValues());
        $formConfig['alias'] = 'form';
        $formConfig['arrayName'] = str_singular(strip_class_basename($model, '_model'));
        $formConfig['context'] = 'edit';

        // Form Widget with extensibility
        /** @var Form $this ->formWidget */
        $this->formWidget = $this->makeWidget(Form::class, $formConfig);
        $this->formWidget->bindToController();

        // Prep the optional toolbar widget
        if (isset($modelConfig['toolbar'], $this->widgets['toolbar'])) {
            $this->toolbarWidget = $this->widgets['toolbar'];
            $this->toolbarWidget->reInitialize($modelConfig['toolbar']);
        }
    }

    protected function findSettingDefinitions(string $code): array
    {
        throw_unless(strlen($code),
            new FlashException(lang('igniter::admin.form.missing_id'))
        );

        // Prep the list widget config
        $model = $this->createModel();

        $definition = $model->getSettingDefinitions($code);

        return [$model, $definition];
    }

    protected function createModel(): SettingsModel
    {
        if (!isset($this->modelClass) || !strlen($this->modelClass)) {
            throw new FlashException(lang('igniter::system.settings.alert_settings_missing_model'));
        }

        return new $this->modelClass;
    }

    protected function formAfterSave(SettingsModel $model)
    {
        $this->validateSettingItems(true);
    }

    protected function getFieldConfig(string $code, SettingsModel $model): array
    {
        $settingItem = $model->getSettingItem('core.'.$code);
        if ($settingItem->form && !is_array($settingItem->form)) {
            $settingItem->form = array_get($this->makeConfig($settingItem->form, ['form']), 'form', []);
        }

        return $settingItem->form ?? [];
    }

    protected function validateSettingItems(bool $skipSession = false): array
    {
        $settingItemErrors = Session::get('settings.errors', []);

        if ($skipSession || !$settingItemErrors) {
            $model = $this->createModel();
            $settingItems = array_get($model->listSettingItems(), 'core');
            $settingValues = array_undot($model->getFieldValues());

            foreach ($settingItems as $settingItem) {
                $settingItemForm = $this->getFieldConfig($settingItem->code, $this->createModel());

                if ($settingItem->request) {
                    $request = new $settingItem->request;
                    $rules = $request->rules();
                    $attributes = $request->attributes();
                } elseif (isset($settingItemForm['rules'])) {
                    $rules = $settingItemForm['rules'];
                    $attributes = [];
                } else {
                    continue;
                }

                $validator = $this->makeValidator($settingValues, $rules, [], $attributes);
                $errors = $validator->fails() ? $validator->errors() : [];

                $settingItemErrors[$settingItem->code] = $errors;
            }

            Session::put('settings.errors', $settingItemErrors);
        }

        return $this->settingItemErrors = $settingItemErrors;
    }
}
