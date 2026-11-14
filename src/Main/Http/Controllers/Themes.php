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
use Igniter\Flame\Exception\FlashException;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Http\Requests\ThemeRequest;
use Igniter\Main\Models\Theme;
use Igniter\Main\Models\ThemePage;
use Igniter\System\Facades\Assets;
use Igniter\System\Traits\ManagesUpdates;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

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

        $this->asExtension('FormController')->edit($context, $themeCode);
    }

    /**
     * Page builder: edit the sections of a theme buildable page. The pages list,
     * the save actions and the page handlers belong to the pagebuilder widget.
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

        Template::setButton(lang('igniter::main.builder.button_customise'), [
            'class' => 'btn btn-default',
            'href' => admin_url('themes/edit/'.$theme->getName()),
        ]);
        $this->offerChildThemeButton($theme->getName());

        $config = $this->makeConfig('igniter::models/main/pagebuilder', ['form'])['form'];
        $config['alias'] = 'builderForm';
        $config['arrayName'] = 'ThemePage';
        $config['context'] = 'builder';
        $config['model'] = ThemePage::firstOrNew(['theme_code' => $theme->getName(), 'page_code' => $pageCode]);

        $formWidget = $this->makeWidget(Form::class, $config);
        $formWidget->bindToController();

        $this->vars['formWidget'] = $formWidget;
    }

    /**
     * Serves a theme screenshot for the themes list, so the list page links to
     * the image instead of inlining it.
     */
    public function screenshot(string $context, string $themeCode): Response
    {
        $path = resolve(ThemeManager::class)->findTheme($themeCode)?->getScreenshotPath();

        // A plain 404, as the admin turns abort() into an error page.
        return $path
            ? response()->file($path, ['Cache-Control' => 'private, max-age=604800'])
            : response('', 404);
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

    public function onCreateChild(string $context, string $themeCode): RedirectResponse
    {
        $manager = resolve(ThemeManager::class);

        $model = $this->formFindModelObject($themeCode);

        throw_unless($manager->findTheme($model->code)?->child, new FlashException(lang('igniter::system.themes.alert_child_disabled')));

        $childTheme = $manager->createChildTheme($model->code);

        Theme::syncAll();
        Theme::activateTheme($childTheme->code);

        flash()->success(sprintf(lang('igniter::admin.alert_success'), 'Child theme ['.$childTheme->name.'] created '));

        // From the page builder, carry on editing the same page in the child theme.
        if ($context === 'builder') {
            return $this->redirect(rtrim('themes/builder/'.$childTheme->code, '/'));
        }

        return $this->redirect('themes/edit/'.$childTheme->code);
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
        $theme = $manager->findTheme($themeCode);
        if (!$manager->isLocked($themeCode) || !$theme?->child || $theme->hasParent()) {
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
        $formConfig['tabs']['fields'] = $formConfig['model']->getFieldsConfig();
        $formConfig['data'] = array_merge($formConfig['model']->toArray(), $formConfig['model']->getFieldValues());
        $formConfig['arrayName'] .= '[data]';
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
        if (config('igniter-system.buildThemeAssetsBundle', true)) {
            Assets::buildBundles($model->getTheme());
        }
    }
}
