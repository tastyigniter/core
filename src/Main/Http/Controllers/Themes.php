<?php

declare(strict_types=1);

namespace Igniter\Main\Http\Controllers;

use Facades\Igniter\System\Helpers\CacheHelper;
use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Admin\Http\Actions\FormController;
use Igniter\Admin\Http\Actions\ListController;
use Igniter\Admin\Widgets\Form;
use Igniter\Admin\Widgets\Toolbar;
use Igniter\Flame\Exception\FlashException;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\FormWidgets\TemplateEditor;
use Igniter\Main\Http\Requests\ThemeRequest;
use Igniter\Main\Models\Theme;
use Igniter\Main\Models\ThemePageLayout;
use Igniter\System\Facades\Assets;
use Igniter\System\Traits\ManagesUpdates;
use Illuminate\Http\RedirectResponse;

class Themes extends AdminController
{
    use ManagesUpdates;

    public array $implement = [
        ListController::class,
        FormController::class,
    ];

    public array $listConfig = [
        'list' => [
            'model' => Theme::class,
            'title' => 'lang:igniter::system.themes.text_title',
            'emptyMessage' => 'lang:igniter::system.themes.text_empty',
            'defaultSort' => ['theme_id', 'DESC'],
            'configFile' => 'theme',
        ],
    ];

    public array $formConfig = [
        'name' => 'lang:igniter::system.themes.text_form_name',
        'model' => Theme::class,
        'request' => ThemeRequest::class,
        'edit' => [
            'title' => 'igniter::system.themes.text_edit_title',
            'redirect' => 'themes/edit/{code}',
            'redirectClose' => 'themes',
        ],
        'source' => [
            'title' => 'igniter::system.themes.text_source_title',
            'redirect' => 'themes/source/{code}',
            'redirectClose' => 'themes',
        ],
        'delete' => [
            'redirect' => 'themes',
        ],
        'configFile' => 'igniter::/models/main/theme',
    ];

    protected null|string|array $requiredPermissions = 'Site.Themes';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('themes', 'design');
    }

    public function index(): void
    {
        Theme::syncAll();

        $this->initUpdate('theme');

        $this->asExtension('ListController')->index();
    }

    public function edit(string $context, string $themeCode): void
    {
        $this->offerChildThemeButton($themeCode);
        $this->offerPageBuilderButton($themeCode);

        Template::setButton(lang('igniter::system.themes.button_source'), [
            'class' => 'btn btn-default pull-right',
            'href' => admin_url('themes/source/'.$themeCode),
        ]);

        $this->asExtension('FormController')->edit($context, $themeCode);
    }

    public function source(string $context, string $themeCode): void
    {
        $this->defaultView = 'edit';
        $this->offerChildThemeButton($themeCode);
        $this->offerPageBuilderButton($themeCode);

        $theme = resolve(ThemeManager::class)->findTheme($themeCode);
        if ($theme && $theme->hasCustomData()) {
            Template::setButton(lang('igniter::system.themes.button_customize'), [
                'class' => 'btn btn-default pull-right',
                'href' => admin_url('themes/edit/'.$themeCode),
            ]);
        }

        $this->asExtension('FormController')->edit($context, $themeCode);
    }

    /**
     * Page builder: edit the sections of a theme buildable page.
     */
    public function builder(string $context, string $themeCode, ?string $pageCode = null): void
    {
        $theme = resolve(ThemeManager::class)->findTheme($themeCode);
        throw_unless($theme, new FlashException(sprintf(lang('igniter::main.builder.alert_theme_not_found'), $themeCode)));

        $pages = $theme->getBuildablePages();
        throw_unless($pages, new FlashException(lang('igniter::main.builder.alert_no_buildable_pages')));

        $pageCode ??= (string)array_key_first($pages);
        throw_unless(isset($pages[$pageCode]), new FlashException(
            sprintf(lang('igniter::main.builder.alert_page_not_found'), $pageCode),
        ));

        // Give the editor the full width, the admin navigation opens from the header
        $this->collapseSidebar = true;

        $pageTitle = sprintf(lang('igniter::main.builder.text_title'), $theme->label ?? $theme->getName());
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        $model = ThemePageLayout::firstOrNew(['theme_code' => $theme->getName(), 'page_code' => $pageCode]);

        /** @var Form $formWidget */
        $formWidget = $this->makeWidget(Form::class, [
            'alias' => 'builderForm',
            'arrayName' => 'ThemePageLayout',
            'model' => $model,
            'context' => 'builder',
            'fields' => [
                'sections' => [
                    'label' => sprintf(lang('igniter::main.builder.label_page_sections'), lang($pages[$pageCode]['label'])),
                    'type' => 'pagebuilder',
                    'themeCode' => $theme->getName(),
                    'pageCode' => $pageCode,
                ],
            ],
        ]);
        $formWidget->bindToController();

        /** @var Toolbar $toolbarWidget */
        $toolbarWidget = $this->widgets['toolbar'];
        $toolbarWidget->reInitialize([
            'buttons' => [
                'save' => [
                    'label' => 'lang:igniter::admin.button_save',
                    'class' => 'btn btn-primary',
                    'data-request' => 'onSave',
                    'data-progress-indicator' => 'igniter::admin.text_saving',
                ],
                'reset' => [
                    'label' => 'lang:igniter::main.builder.button_reset',
                    'class' => 'btn btn-default',
                    'data-request' => 'onReset',
                    'data-request-confirm' => 'lang:igniter::main.builder.confirm_reset',
                    'data-progress-indicator' => 'igniter::admin.text_saving',
                ],
            ],
        ]);

        $this->vars['builderTheme'] = $theme;
        $this->vars['builderPages'] = $pages;
        $this->vars['builderPageCode'] = $pageCode;
        $this->vars['builderPageUrl'] = $theme->isActive() ? rescue(fn(): string => page_url($pageCode), null, false) : null;
        $this->vars['formWidget'] = $formWidget;
        $this->vars['toolbarWidget'] = $toolbarWidget;
    }

    public function builder_onSave(string $context, string $themeCode, ?string $pageCode = null): RedirectResponse
    {
        $this->builder($context, $themeCode, $pageCode);

        $formWidget = $this->vars['formWidget'];
        $model = $formWidget->model;
        $model->sections = array_get($formWidget->getSaveData(), 'sections', []);
        $model->save();

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::main.builder.text_page_saved')));

        return $this->redirect('themes/builder/'.$themeCode.'/'.$this->vars['builderPageCode']);
    }

    public function builder_onReset(string $context, string $themeCode, ?string $pageCode = null): RedirectResponse
    {
        $this->builder($context, $themeCode, $pageCode);

        $this->vars['formWidget']->model->delete();

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::main.builder.text_page_reset')));

        return $this->redirect('themes/builder/'.$themeCode.'/'.$this->vars['builderPageCode']);
    }

    public function delete(string $context, string $themeCode): ?RedirectResponse
    {
        $pageTitle = lang('igniter::system.themes.text_delete_title');
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        /** @var Theme $model */
        $model = Theme::whereCode($themeCode)->first();

        // Theme must be disabled before it can be deleted
        if ($model && $model->isDefault()) {
            flash()->warning(sprintf(
                lang('igniter::admin.alert_error_nothing'),
                lang('igniter::admin.text_deleted').lang('igniter::system.themes.text_theme_is_active'),
            ));

            return $this->redirectBack();
        }

        // Theme not found in filesystem so delete from database
        $themeManager = resolve(ThemeManager::class);
        $theme = $themeManager->findTheme($themeCode);
        if (!$theme) {
            $model->delete();
            flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Theme deleted '));

            return $this->redirectBack();
        }

        // Let's display a delete confirmation screen
        // with list of files to be deleted
        $this->vars['themeModel'] = $model;
        $this->vars['themeObj'] = $theme;
        $this->vars['themeData'] = $model->data;

        return null;
    }

    public function index_onSetDefault(): RedirectResponse
    {
        $data = $this->validate(post(), [
            'code' => 'required|alpha_dash|exists:'.Theme::class.',code',
        ]);

        if ($theme = Theme::activateTheme($data['code'])) {
            CacheHelper::clearView();

            flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Theme ['.$theme->name.'] set as default '));
        }

        return $this->redirectBack();
    }

    public function edit_onReset(string $context, string $themeCode): ?RedirectResponse
    {
        $formController = $this->asExtension('FormController');
        $model = $this->formFindModelObject($themeCode);
        $formController->initForm($model, $context);

        $model->data = [];
        $model->save();

        $this->formAfterSave($model);

        return $formController->makeRedirect($context, $model) ?: null;
    }

    public function source_onSave(string $context, string $themeCode): ?RedirectResponse
    {
        $this->defaultView = 'edit';
        $formController = $this->asExtension('FormController');
        $model = $this->formFindModelObject($themeCode);
        $formController->initForm($model, $context);

        /** @var TemplateEditor $formTemplate */
        $formTemplate = $this->widgets['formTemplate'];
        $formTemplate->onSaveSource();

        flash()->success(
            sprintf(lang('igniter::admin.form.edit_success'), lang('lang:igniter::system.themes.text_form_name')),
        );

        return $formController->makeRedirect($context, $model) ?: null;
    }

    public function onCreateChild(string $context, string $themeCode): RedirectResponse
    {
        $manager = resolve(ThemeManager::class);

        $model = $this->formFindModelObject($themeCode);

        throw_unless($manager->findTheme($model->code)?->child, new FlashException(lang('igniter::system.themes.alert_child_disabled')));

        $childTheme = $manager->createChildTheme($model->code);

        Theme::syncAll();
        Theme::activateTheme($childTheme->code);

        flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Child theme ['.$childTheme->name.'] created '));

        return $this->redirect('themes/source/'.$childTheme->code);
    }

    public function delete_onDelete(string $context, string $themeCode): RedirectResponse
    {
        /** @var Theme $model */
        $model = Theme::whereCode($themeCode)->first();
        if ($model && $model->isDefault()) {
            flash()->warning(sprintf(
                lang('igniter::admin.alert_error_nothing'),
                lang('igniter::admin.text_deleted').lang('igniter::system.themes.text_theme_is_active'),
            ));

            return $this->redirect('themes');
        }

        resolve(ThemeManager::class)->deleteTheme($themeCode, post('delete_data', 1) == 1);
        flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Theme deleted '));

        return $this->redirect('themes');
    }

    protected function offerPageBuilderButton(string $themeCode): void
    {
        if (!resolve(ThemeManager::class)->findTheme($themeCode)?->getBuildablePages()) {
            return;
        }

        Template::setButton(lang('igniter::main.builder.button_page_builder'), [
            'class' => 'btn btn-default pull-right ms-2',
            'href' => admin_url('themes/builder/'.$themeCode),
        ]);
    }

    protected function offerChildThemeButton(string $themeCode): void
    {
        $manager = resolve(ThemeManager::class);
        if (!$manager->isLocked($themeCode) || !$manager->findTheme($themeCode)?->child) {
            return;
        }

        Template::setButton(lang('igniter::system.themes.button_child'), [
            'class' => 'btn btn-default pull-right ms-2',
            'data-request' => 'onCreateChild',
        ]);
    }

    public function listOverrideColumnValue($record, $column, $alias = null): ?array
    {
        if ($column->type != 'button' || $column->columnName != 'default') {
            return null;
        }

        $attributes = $column->attributes;

        $column->iconCssClass = 'fa fa-star-o';
        if ($record->getTheme() && $record->getTheme()->isActive()) {
            $column->iconCssClass = 'fa fa-star';
            $attributes['title'] = 'lang:igniter::system.themes.text_is_default';
            $attributes['data-request'] = null;
        }

        return $attributes;
    }

    public function formExtendConfig(array &$formConfig): void
    {
        $formConfig['data'] = $formConfig['model']->toArray();

        if ($formConfig['context'] != 'source') {
            $formConfig['tabs']['fields'] = $formConfig['model']->getFieldsConfig();
            $formConfig['data'] = array_merge($formConfig['data'], $formConfig['model']->getFieldValues());
            $formConfig['arrayName'] .= '[data]';

            return;
        }

        $formConfig['arrayName'] .= '[source]';
    }

    public function formFindModelObject(string $recordId): Theme
    {
        throw_unless(strlen($recordId), new FlashException(lang('igniter::admin.form.missing_id')));

        $model = $this->asExtension(FormController::class)->formCreateModelObject();

        // Prepare query and find model record
        $query = $model->newQuery();
        $this->fireEvent('admin.controller.extendFormQuery', [$query]);
        $result = $query->where('code', $recordId)->first();

        throw_unless($result, new FlashException(sprintf(lang('igniter::admin.form.not_found'), $recordId)));

        return $result;
    }

    public function formAfterSave(Theme $model): void
    {
        /** @var Form $form */
        $form = $this->widgets['form'];
        if ($form->context != 'source' && config('igniter-system.buildThemeAssetsBundle', true)) {
            Assets::buildBundles($model->getTheme());
        }
    }
}
