<?php

declare(strict_types=1);

namespace Igniter\Main\FormWidgets;

use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Exception\FlashException;
use Igniter\Main\Classes\BaseBlock;
use Igniter\Main\Classes\BlockManager;
use Igniter\Main\Classes\PageBuilder as PageBuilderService;
use Igniter\Main\Classes\SectionLayouts;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Http\Requests\ThemePageRequest;
use Igniter\Main\Models\ThemePage;
use Igniter\Main\Template\Layout as LayoutTemplate;
use Igniter\Main\Template\Page as PageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Override;

/**
 * Page builder form widget.
 *
 * Edits a page as a sortable list of sections. Each section has a column layout
 * and blocks are added inside the columns. The value is the sections array,
 * posted as a single JSON string.
 *
 * Usage:
 * <pre>
 *   'sections' => [
 *       'type' => 'pagebuilder',
 *       'themeCode' => 'igniter-orange',  // defaults to the active theme
 *       'pageCode' => 'home',             // used for required and page restricted blocks
 *   ]
 * </pre>
 *
 * With `showPages`, the widget edits a theme's pages: it lists them beside the
 * sections, saves the sections to its ThemePage model, and adds, renames,
 * deletes and edits the settings of the theme's page files. The theme and page
 * then default to the model's. See igniter::models/main/pagebuilder.
 */
class PageBuilder extends BaseFormWidget
{
    //
    // Configurable properties
    //

    public ?string $themeCode = null;

    public ?string $pageCode = null;

    /** Show the theme's pages beside the sections, with the page and save actions. */
    public bool $showPages = false;

    /** Admin URL of a page in the builder, with {theme} and {page} placeholders. */
    public string $pageUrl = 'themes/builder/{theme}/{page}';

    //
    // Object properties
    //

    protected string $defaultAlias = 'pagebuilder';

    /** Page settings are saved to the page file. */
    public const string SETTINGS_FILE = 'file';

    /** The theme is locked and offers a child theme to edit the page settings in. */
    public const string SETTINGS_CHILD = 'child';

    /** Page settings are saved in theme_pages.settings. */
    public const string SETTINGS_DATA = 'data';

    protected ?Theme $theme = null;

    #[Override]
    public function initialize(): void
    {
        $this->fillFromConfig([
            'themeCode',
            'pageCode',
            'showPages',
            'pageUrl',
        ]);

        if ($this->model instanceof ThemePage) {
            $this->themeCode ??= $this->model->theme_code;
            $this->pageCode ??= $this->model->page_code;
        }

        $themeCode = $this->themeCode ?? Theme::getActiveCode();
        $theme = $themeCode ? resolve(ThemeManager::class)->findTheme($themeCode) : null;

        throw_unless($theme, new FlashException(sprintf(lang('igniter::main.builder.alert_theme_not_found'), $themeCode)));

        $this->theme = $theme;
    }

    #[Override]
    public function loadAssets(): void
    {
        $this->addCss('pagebuilder.css', 'pagebuilder-css');
        $this->addJs('pagebuilder.js', 'pagebuilder-js');

        if ($this->showPages) {
            // The page name and settings forms open in a record editor modal.
            $this->addJs('igniter::js/formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
        }
    }

    #[Override]
    public function render(): string
    {
        $this->prepareVars();
        $this->preloadFieldAssets();

        return $this->makePartial('pagebuilder/pagebuilder');
    }

    public function prepareVars(): void
    {
        $sections = $this->getSections();

        $this->vars['field'] = $this->formField;
        $this->vars['sections'] = $sections;
        $this->vars['editorConfig'] = $this->getEditorConfig($sections);
        $this->vars['layouts'] = $this->getLayoutOptions();
        $this->vars['showPages'] = $this->showPages;

        if ($this->showPages) {
            $this->preparePagesVars();
        }
    }

    protected function preparePagesVars(): void
    {
        $pages = $this->theme->getBuildablePages();

        $this->vars['theme'] = $this->theme;
        $this->vars['pages'] = $pages;
        $this->vars['pageCode'] = $this->pageCode;
        $this->vars['pageLabel'] = lang($pages[$this->pageCode]['label'] ?? (string)$this->pageCode);
        // Pages made with the builder can be renamed and deleted when the theme files can be written.
        $this->vars['canEditPages'] = !$this->theme->locked && !$this->previewMode;
        $this->vars['canAddPages'] = $this->theme->canAddBuilderPages();
        $this->vars['viewPageUrl'] = $this->theme->isActive() && $this->pageCode
            ? rescue(fn(): string => page_url($this->pageCode), null, false)
            : null;
    }

    public function getTheme(): Theme
    {
        return $this->theme;
    }

    /**
     * Returns the normalized sections, including any required blocks.
     */
    public function getSections(): array
    {
        $value = $this->getLoadValue();

        // Nothing saved yet, start from the theme default layout for the page
        $sections = is_null($value) && $this->pageCode
            ? $this->getService()->defaultSections($this->theme, $this->pageCode)
            : $this->getService()->normalize($this->theme, $this->decodeValue($value), $this->pageCode);

        return $this->getService()->ensureRequiredBlocks($this->theme, $sections, $this->pageCode);
    }

    #[Override]
    public function getSaveValue(mixed $value): array
    {
        $sections = $this->getService()->normalize($this->theme, $this->decodeValue($value), $this->pageCode);

        return $this->getService()->ensureRequiredBlocks($this->theme, $sections, $this->pageCode);
    }

    //
    // Handlers
    //

    public function onLoadBlockForm(): string
    {
        $block = $this->findBlockOrFail((string)post('code'));
        $settings = array_merge($block->defaults(), $this->decodeValue(post('settings')));

        return $this->makePartial('pagebuilder/form', [
            'formTitle' => sprintf(lang('igniter::main.builder.text_edit_block'), $block->getName()),
            'formWidget' => $this->makeSettingsFormWidget('block', $block->formFields(), $settings),
            'formHandler' => $this->getEventHandler('onValidateBlockForm'),
            'formCode' => $block->getCode(),
        ]);
    }

    public function onValidateBlockForm(): array
    {
        $block = $this->findBlockOrFail((string)post('code'));

        $formWidget = $this->makeSettingsFormWidget('block', $block->formFields(), $block->defaults());
        $settings = array_merge($block->defaults(), $formWidget->getSaveData());

        Validator::make($settings, $block->validationRules(), [], $this->getFieldLabels($block->formFields()))->validate();

        $settings = $block->sanitize($settings);

        return [
            'settings' => $settings,
            'summary' => $block->summary($settings),
        ];
    }

    public function onLoadSectionForm(): string
    {
        $settings = SectionLayouts::normalizeSettings($this->decodeValue(post('settings')));

        return $this->makePartial('pagebuilder/form', [
            'formTitle' => lang('igniter::main.builder.text_section_settings'),
            'formWidget' => $this->makeSettingsFormWidget('section', SectionLayouts::settingsFields(), $settings),
            'formHandler' => $this->getEventHandler('onValidateSectionForm'),
            'formCode' => null,
        ]);
    }

    public function onValidateSectionForm(): array
    {
        $formWidget = $this->makeSettingsFormWidget('section', SectionLayouts::settingsFields(), SectionLayouts::defaultSettings());

        return [
            'settings' => SectionLayouts::normalizeSettings($formWidget->getSaveData()),
        ];
    }

    //
    // Page handlers, used with showPages
    //

    public function onSavePage(): RedirectResponse
    {
        $model = $this->getPageLayoutModel();
        $model->sections = $this->getSaveValue(post($this->formField->getName()));
        $model->save();

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::main.builder.text_page_saved')));

        return $this->redirectToPage($this->pageCode);
    }

    public function onResetPage(): RedirectResponse
    {
        // Keep the row when it holds the page settings, only the sections reset.
        $model = $this->getPageLayoutModel();
        if ($model->settings) {
            $model->sections = null;
            $model->save();
        } else {
            $model->delete();
        }

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::main.builder.text_page_reset')));

        return $this->redirectToPage($this->pageCode);
    }

    /**
     * The settings of a page. They are saved to the page file when the theme can be
     * written. A locked theme that allows child themes offers to create one, and
     * other pages save their settings in theme_pages.
     */
    public function onLoadPageSettings(): string
    {
        $pageCode = $this->findBuildablePage((string)post('recordId'));
        $pageLabel = lang($this->theme->getBuildablePage($pageCode)['label'] ?? $pageCode);

        if ($this->pageSettingsMode($pageCode) === self::SETTINGS_CHILD) {
            return $this->makePartial('pagebuilder/page_locked_modal', [
                'pageCode' => $pageCode,
                'pageLabel' => $pageLabel,
            ]);
        }

        return $this->makePartial('pagebuilder/page_settings_modal', [
            'formWidget' => $this->makePageSettingsForm($pageCode),
            'formHandler' => $this->getEventHandler('onSavePageSettings'),
            'pageCode' => $pageCode,
            'pageLabel' => $pageLabel,
        ]);
    }

    public function onSavePageSettings(): RedirectResponse
    {
        $pageCode = $this->findBuildablePage((string)post('pageCode'));
        $mode = $this->pageSettingsMode($pageCode);
        throw_if($mode === self::SETTINGS_CHILD, new FlashException(lang('igniter::system.themes.alert_theme_locked')));

        $formWidget = $this->makePageSettingsForm($pageCode);
        resolve(ThemePageRequest::class);

        $settings = array_except((array)array_get($formWidget->getSaveData(), 'settings', []), PageTemplate::PROTECTED_SETTINGS);
        if (array_key_exists('isHidden', $settings)) {
            $settings['isHidden'] = (bool)$settings['isHidden'];
        }

        $themeCode = $this->theme->getName();
        if ($mode === self::SETTINGS_FILE) {
            $this->theme->findPageFile($pageCode)?->fill($settings)->save();
            // The file holds the settings now, drop any saved while the theme was locked.
            if (ThemePage::settingsFor($themeCode, $pageCode)) {
                ThemePage::saveSettings($themeCode, $pageCode, []);
            }
        } else {
            ThemePage::saveSettings($themeCode, $pageCode, $settings);
        }

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::main.builder.text_page_settings_saved')));

        return $this->redirectToPage($pageCode);
    }

    /**
     * The form to name a new page, or to rename one made with the builder.
     */
    public function onLoadPageName(): string
    {
        $this->assertThemeWritable();

        $pageCode = (string)post('recordId');
        $label = '';
        if ($pageCode !== '') {
            [$page] = $this->findPageFile($pageCode, true);
            $label = (string)$page->title;
        } else {
            throw_unless($this->theme->canAddBuilderPages(), new FlashException(lang('igniter::system.themes.alert_theme_locked')));
        }

        return $this->makePartial('pagebuilder/page_name_modal', [
            'formHandler' => $this->getEventHandler($pageCode !== '' ? 'onRenamePage' : 'onCreatePage'),
            'pageCode' => $pageCode,
            'pageLabel' => $label,
        ]);
    }

    public function onCreatePage(): RedirectResponse
    {
        $this->assertThemeWritable();
        throw_unless($this->theme->canAddBuilderPages(), new FlashException(lang('igniter::system.themes.alert_theme_locked')));

        $name = $this->validatePageName();
        $fileName = $this->uniquePageFileName($name);

        PageTemplate::on($this->theme->getName())->fill([
            'fileName' => $fileName,
            'title' => $name,
            'permalink' => '/'.$fileName,
            'layout' => $this->defaultPageLayout(),
            'description' => '',
            'markup' => Theme::BUILDER_PAGE_MARKUP,
        ])->save();

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::main.builder.text_page_created')));

        return $this->redirectToPage($fileName);
    }

    public function onRenamePage(): RedirectResponse
    {
        [$page, $pageCode] = $this->findPageFile((string)post('pageCode'), true);

        $name = $this->validatePageName();
        $fileName = Str::slug($name) === $pageCode ? $pageCode : $this->uniquePageFileName($name);

        $page->fill(['fileName' => $fileName, 'title' => $name])->save();

        ThemePage::where('theme_code', $this->theme->getName())
            ->where('page_code', $pageCode)
            ->update(['page_code' => $fileName]);

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::main.builder.text_page_renamed')));

        return $this->redirectToPage($fileName);
    }

    public function onDeletePage(): RedirectResponse
    {
        [$page, $pageCode] = $this->findPageFile((string)post('pageCode'), true);

        $page->delete();
        ThemePage::where('theme_code', $this->theme->getName())->where('page_code', $pageCode)->delete();

        flash()->success(sprintf(lang('igniter::admin.alert_success'), lang('igniter::main.builder.text_page_deleted')));

        return $this->redirectToPage(null);
    }

    //
    // Helpers
    //

    /**
     * The layout saved for the page, used by the page handlers.
     */
    protected function getPageLayoutModel(): ThemePage
    {
        throw_unless($this->showPages && $this->model instanceof ThemePage, new FlashException(
            sprintf(lang('igniter::main.builder.alert_page_not_found'), (string)$this->pageCode),
        ));

        return $this->model;
    }

    protected function assertThemeWritable(): void
    {
        $this->getPageLayoutModel();

        throw_if($this->theme->locked, new FlashException(lang('igniter::system.themes.alert_theme_locked')));
    }

    /**
     * The page file of a builder page. Pages made with the builder can also be
     * renamed and deleted; the theme's own pages only have their settings edited.
     *
     * @return array{0: PageTemplate, 1: string}
     */
    protected function findPageFile(string $pageCode, bool $madeWithBuilder = false): array
    {
        $this->assertThemeWritable();

        $page = $this->theme->getBuildablePage($pageCode);
        throw_unless($page, new FlashException(sprintf(lang('igniter::main.builder.alert_page_not_found'), $pageCode)));
        throw_if($madeWithBuilder && !$page['custom'], new FlashException(lang('igniter::main.builder.alert_page_not_custom')));

        $pageFile = $this->theme->findPageFile($pageCode);
        throw_unless($pageFile, new FlashException(sprintf(lang('igniter::main.builder.alert_page_not_found'), $pageCode)));
        throw_if(
            $madeWithBuilder && resolve(ThemeManager::class)->isLockedPath($pageFile->getFilePath(), $this->theme),
            new FlashException(lang('igniter::system.themes.alert_theme_path_locked')),
        );

        return [$pageFile, $pageCode];
    }

    /**
     * Where the settings of a page are saved: its file when the theme can be written,
     * else theme_pages. A locked theme that allows child themes offers one instead.
     */
    public function pageSettingsMode(string $pageCode): string
    {
        if (!$this->theme->findPageFile($pageCode) instanceof PageTemplate) {
            return self::SETTINGS_DATA;
        }

        if (!$this->theme->locked) {
            return self::SETTINGS_FILE;
        }

        return $this->theme->child && !$this->theme->hasParent() ? self::SETTINGS_CHILD : self::SETTINGS_DATA;
    }

    protected function findBuildablePage(string $pageCode): string
    {
        $this->getPageLayoutModel();

        throw_unless($this->theme->getBuildablePage($pageCode), new FlashException(
            sprintf(lang('igniter::main.builder.alert_page_not_found'), $pageCode),
        ));

        return $pageCode;
    }

    protected function makePageSettingsForm(string $pageCode): Form
    {
        $pageModel = ThemePage::firstOrNew(['theme_code' => $this->theme->getName(), 'page_code' => $pageCode]);

        // A page without a file still gets a page object, so extensions that add
        // fields to page settings forms (e.g. SEO Manager) add them here too.
        $page = $this->theme->findPageFile($pageCode);
        $settings = $page instanceof PageTemplate ? (array)$page->settings : (array)$pageModel->settings;
        $fileSource = $page ?? PageTemplate::on($this->theme->getName())->fill(array_merge(
            $settings, ['fileName' => str_replace('.', '/', $pageCode)],
        ));

        $config = $this->makeConfig('igniter::models/main/page', ['form'])['form'];
        if (!$page instanceof PageTemplate) {
            // The address, layout and visibility belong to the page file.
            $config['tabs']['fields'] = array_except($config['tabs']['fields'], [
                'settings[permalink]', 'settings[layout]', 'settings[isHidden]',
            ]);
        }

        $config['alias'] = $this->alias.'PageSettings';
        $config['arrayName'] = 'PageSettings';
        $config['context'] = 'edit';
        // The form is shown in a modal, so switching tabs should not send a request.
        $config['storeActiveTab'] = false;
        $config['model'] = $pageModel;
        $config['data'] = [
            'fileName' => $fileSource->getFileName(),
            'baseFileName' => $fileSource->getBaseFileName(),
            'settings' => $settings,
            'fileSource' => $fileSource,
        ];

        /** @var Form $formWidget */
        $formWidget = $this->makeWidget(Form::class, $config);
        $formWidget->bindToController();

        return $formWidget;
    }

    protected function validatePageName(): string
    {
        $data = Validator::make(post(), [
            'name' => ['required', 'string', 'max:160', 'regex:/[\pL\pN]/u'],
        ], [], [
            'name' => lang('igniter::main.builder.label_page_name'),
        ])->validate();

        return trim((string)$data['name']);
    }

    protected function uniquePageFileName(string $name): string
    {
        $base = Str::slug($name) ?: 'page';
        $fileName = $base;
        for ($i = 2; $this->theme->getBuildablePage($fileName) || $this->theme->findPageFile($fileName) instanceof PageTemplate; $i++) {
            $fileName = $base.'-'.$i;
        }

        return $fileName;
    }

    protected function defaultPageLayout(): string
    {
        $layouts = array_keys(LayoutTemplate::getDropdownOptions($this->theme->getName()));

        return in_array('default', $layouts, true) ? 'default' : (string)($layouts[0] ?? '');
    }

    /**
     * Redirects to a page in the builder, or to the theme's first page.
     */
    protected function redirectToPage(?string $pageCode): RedirectResponse
    {
        $url = strtr($this->pageUrl, ['{theme}' => $this->theme->getName(), '{page}' => (string)$pageCode]);

        return $this->controller->redirect(rtrim($url, '/'));
    }

    protected function getService(): PageBuilderService
    {
        return resolve(PageBuilderService::class);
    }

    /**
     * Data used by the editor script: available blocks, layouts, required blocks,
     * block summaries and translated messages.
     */
    protected function getEditorConfig(array $sections): array
    {
        $blocks = resolve(BlockManager::class)->listBlocks($this->theme, $this->pageCode);
        $required = $this->getService()->requiredBlocks($this->theme, $this->pageCode);

        $summaries = [];
        foreach ($sections as $section) {
            foreach ($section['columns'] as $column) {
                foreach ($column['blocks'] as $block) {
                    $summaries[$block['id']] = $blocks[$block['code']]->summary($block['settings']);
                }
            }
        }

        return [
            'alias' => $this->alias,
            'previewMode' => $this->previewMode,
            'blocks' => array_map(fn(BaseBlock $block): array => [
                'code' => $block->getCode(),
                'name' => $block->getName(),
                'description' => lang((string)$block->getDetails()['description']),
                'icon' => $block->getDetails()['icon'],
                'unique' => $block->isUnique(),
                'wide' => $block->isWide(),
                // Every block has the CSS class and HTML attributes fields.
                'hasFields' => true,
                'required' => in_array($block->getCode(), $required, true),
                'defaults' => $block->sanitize($block->defaults()),
            ], $blocks),
            'layouts' => $this->getLayoutOptions(),
            'defaultSectionSettings' => SectionLayouts::defaultSettings(),
            'wideSpan' => SectionLayouts::WIDE_SPAN,
            'summaries' => $summaries,
            'messages' => [
                'confirmRemoveBlock' => lang('igniter::main.builder.confirm_remove_block'),
                'confirmRemoveSection' => lang('igniter::main.builder.confirm_remove_section'),
                'confirmLayoutChange' => lang('igniter::main.builder.confirm_layout_change'),
                'alertWideBlock' => lang('igniter::main.builder.alert_wide_block'),
                'alertRequiredBlock' => lang('igniter::main.builder.alert_required_block'),
                'alertRequestFailed' => lang('igniter::main.builder.alert_request_failed'),
                'editBlock' => lang('igniter::main.builder.button_edit_block'),
            ],
        ];
    }

    /**
     * @return array<string, array{code: string, label: string, columns: int, equal: bool, spans: list<int|null>}>
     */
    protected function getLayoutOptions(): array
    {
        $layouts = [];
        foreach (SectionLayouts::options() as $code => $label) {
            $layouts[$code] = [
                'code' => $code,
                'label' => $label,
                'columns' => SectionLayouts::columnCount($code),
                'equal' => SectionLayouts::isEqual($code),
                'spans' => SectionLayouts::spans($code),
            ];
        }

        return $layouts;
    }

    protected function findBlockOrFail(string $code): BaseBlock
    {
        $block = resolve(BlockManager::class)->findBlock($this->theme, $code, $this->pageCode);

        throw_unless($block, new FlashException(sprintf(lang('igniter::main.builder.alert_block_not_found'), $code)));

        return $block;
    }

    /**
     * Settings forms get a blank model of their own. Using the parent form model
     * would let extensions that extend that model's forms (e.g. adding tabbed
     * fields to every static page form) act on these small settings forms.
     */
    protected function makeSettingsFormWidget(string $context, array $fields, array $data): Form
    {
        /** @var Form $widget */
        $widget = $this->makeWidget(Form::class, [
            'alias' => $this->alias.ucfirst($context).'Form',
            'arrayName' => $this->alias.'Settings',
            'model' => new ThemePage,
            'data' => $data,
            'fields' => $fields,
            'context' => $context,
            'previewMode' => $this->previewMode,
        ]);

        $widget->bindToController();

        return $widget;
    }

    /**
     * Block and section settings forms are loaded into a modal over AJAX, which
     * cannot add field widget scripts and styles to the page. Build one form
     * holding every field type in use so their assets load with the page.
     */
    protected function preloadFieldAssets(): void
    {
        if ($this->previewMode) {
            return;
        }

        $fields = [];
        $definitions = array_map(fn(BaseBlock $block): array => $block->formFields(),
            resolve(BlockManager::class)->listBlocks($this->theme, $this->pageCode));
        $definitions[] = SectionLayouts::settingsFields();

        foreach ($definitions as $blockFields) {
            foreach ($blockFields as $field) {
                $type = $field['type'] ?? 'text';
                $fields['_preload_'.$type] ??= ['type' => $type];
            }
        }

        $this->makeSettingsFormWidget('assets', $fields, []);
    }

    protected function getFieldLabels(array $fields): array
    {
        return array_map(fn(array $field): string => lang((string)($field['label'] ?? '')), $fields);
    }

    /**
     * Accepts the sections/settings as an array or JSON string.
     */
    protected function decodeValue(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }
}
