<?php

namespace Igniter\Tests\Main\FormWidgets;

use Exception;
use Igniter\Admin\Classes\FormField;
use Igniter\Flame\Exception\FlashException;
use Igniter\Main\Classes\Theme as ThemeClass;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\FormWidgets\TemplateEditor;
use Igniter\Main\Models\Theme;
use Igniter\Main\Template\Page as PageTemplate;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

beforeEach(function() {
    app()->instance(ThemeManager::class, $this->themeManager = mock(ThemeManager::class));
    $this->themeManager->shouldReceive('isLocked')->with('tests-theme')->andReturnFalse()->byDefault();
    $this->themeManager->shouldReceive('readFile')->andReturn(new PageTemplate([
        'file_name' => 'components',
        'content' => 'new content',
    ]))->byDefault();
    $this->controller = resolve(TestController::class);
    $this->formField = new FormField('template', 'Template Editor');
    $this->formField->displayAs('templateeditor');
    $this->formField->arrayName = 'theme';
    $this->templateEditorWidget = new TemplateEditor($this->controller, $this->formField, [
        'model' => Theme::factory()->make(['code' => 'tests-theme']),
    ]);
});

it('initializes correctly', function() {
    expect($this->templateEditorWidget->form)->toBeNull()
        ->and($this->templateEditorWidget->placeholder)->toBe('igniter::system.themes.text_select_file')
        ->and($this->templateEditorWidget->formName)->toBe('igniter::system.themes.label_template')
        ->and($this->templateEditorWidget->addLabel)->toBe('igniter::system.themes.button_new_source')
        ->and($this->templateEditorWidget->editLabel)->toBe('igniter::system.themes.button_rename_source')
        ->and($this->templateEditorWidget->deleteLabel)->toBe('igniter::system.themes.button_delete_source');
});

it('renders correctly', function() {
    $this->themeManager->shouldReceive('findTheme')->with('tests-theme')->andReturn($theme = mock(ThemeClass::class));
    $this->themeManager->shouldReceive('getActiveTheme')->andReturn($theme);
    $theme->shouldReceive('getTemplateClass')->andReturn(PageTemplate::class);
    $theme->shouldReceive('getName')->andReturn('tests-theme');
    $theme->shouldReceive('getAssetPath')->andReturn('/path/to/asset');
    $theme->shouldReceive('hasParent')->andReturn(false);

    expect($this->templateEditorWidget->render())->toBeString();

    $formWidget = $this->templateEditorWidget->vars['templateWidget']->getFormWidget('settings[components]');
    $formWidget->fireEvent('partialCopied', ['partialName']);
    $formWidget->fireEvent('updated', ['partialName']);
});

it('throws exception when rendering widget with invalid theme', function() {
    $this->themeManager->shouldReceive('findTheme')->with('tests-theme')->andReturnNull();

    expect(fn() => $this->templateEditorWidget->render())->toThrow(FlashException::class);
});

it('reloads widget correctly', function() {
    $this->themeManager->shouldReceive('findTheme')->with('tests-theme')->andReturn($theme = mock(ThemeClass::class));
    $this->themeManager->shouldReceive('getActiveTheme')->andReturn($theme);
    $theme->shouldReceive('getTemplateClass')->andReturn(PageTemplate::class);
    $theme->shouldReceive('getName')->andReturn('tests-theme');

    expect($this->templateEditorWidget->reload())->toBeArray()
        ->toHaveKey('#'.$this->templateEditorWidget->getId('container'));
});

it('throws exception when reloading widget with invalid template file', function() {
    $this->themeManager->shouldReceive('readFile')->andThrow(Exception::class);
    $this->themeManager->shouldReceive('findTheme')->with('tests-theme')->andReturn($theme = mock(ThemeClass::class));
    $theme->shouldReceive('getTemplateClass')->andReturn(PageTemplate::class);
    $theme->shouldReceive('getName')->andReturn('tests-theme');

    expect($this->templateEditorWidget->reload())->toBeArray();
});

it('chooses template file correctly', function() {
    request()->request->add([
        'Theme' => [
            'source' => [
                'template' => [
                    'type' => '_pages',
                    'file' => 'components',
                ],
            ],
        ],
    ]);

    expect($this->templateEditorWidget->onChooseFile())->toBeInstanceOf(RedirectResponse::class)
        ->and($this->templateEditorWidget->getTemplateValue('type'))->toBe('_pages')
        ->and($this->templateEditorWidget->getTemplateValue('file'))->toBe('components');
});

it('throws exception when renaming, deleting or creating template file of locked theme', function() {
    $this->themeManager->shouldReceive('isLocked')->with('tests-theme')->andReturnTrue();

    expect(fn() => $this->templateEditorWidget->onManageSource())
        ->toThrow(FlashException::class, lang('igniter::system.themes.alert_theme_locked'));
});

it('renames template file', function() {
    $this->themeManager->shouldReceive('renameFile')->once()->with(
        '_pages/components',
        '_pages/new-components',
        'tests-theme',
    );

    $this->templateEditorWidget->setTemplateValue('type', '_pages');
    $this->templateEditorWidget->setTemplateValue('file', 'components');

    request()->request->add([
        'action' => 'rename',
        'name' => 'new-components',
    ]);

    expect($this->templateEditorWidget->onManageSource())->toBeInstanceOf(RedirectResponse::class)
        ->and($this->templateEditorWidget->getTemplateValue('file'))->toBe('new-components');
});

it('creates new template file', function() {
    $this->themeManager->shouldReceive('newFile')->once()->with('_pages/new-file', 'tests-theme');

    $this->templateEditorWidget->setTemplateValue('type', '_pages');

    request()->request->add([
        'action' => 'new',
        'name' => 'new-file',
    ]);

    expect($this->templateEditorWidget->onManageSource())->toBeInstanceOf(RedirectResponse::class)
        ->and($this->templateEditorWidget->getTemplateValue('file'))->toBe('new-file');
});

it('deletes template file', function() {
    $this->themeManager->shouldReceive('deleteFile')->once()->with('_pages/components', 'tests-theme');

    $this->templateEditorWidget->setTemplateValue('type', '_pages');
    $this->templateEditorWidget->setTemplateValue('file', 'components');

    request()->request->add([
        'action' => 'delete',
        'name' => '',
    ]);

    expect($this->templateEditorWidget->onManageSource())->toBeInstanceOf(RedirectResponse::class)
        ->and($this->templateEditorWidget->getTemplateValue('file'))->toBe('');
});

it('throws exception when updating template file content of locked theme', function() {
    $this->themeManager->shouldReceive('isLocked')->with('tests-theme')->andReturnTrue();

    expect(fn() => $this->templateEditorWidget->onSaveSource())
        ->toThrow(FlashException::class, lang('igniter::system.themes.alert_theme_locked'));
});

it('fails validation when template file has been modified in a different session', function() {
    $this->templateEditorWidget->setTemplateValue('type', '_pages');
    $this->templateEditorWidget->setTemplateValue('file', 'components');
    $this->templateEditorWidget->setTemplateValue('mTime', 'components');

    request()->request->add([
        'Theme' => [
            'source' => [
                'type' => '_pages',
                'file' => 'components',
                'content' => 'new content',
            ],
        ],
    ]);

    expect(fn() => $this->templateEditorWidget->onSaveSource())->toThrow(ValidationException::class);
});

it('updates template file content', function() {
    $this->themeManager->shouldReceive('findTheme')->with('tests-theme')->andReturn($theme = mock(ThemeClass::class));
    $theme->shouldReceive('getTemplateClass')->andReturn(PageTemplate::class);
    $theme->shouldReceive('getName')->andReturn('tests-theme');

    $this->templateEditorWidget->prepareVars();
    $this->templateEditorWidget->vars['templateWidget']->data->fileSource = $pageTemplate = mock(PageTemplate::class);
    $pageTemplate->shouldReceive('fill')->andReturnSelf();
    $pageTemplate->shouldReceive('save')->andReturnTrue();
    $pageTemplate->shouldReceive('hasGetMutator')->andReturnFalse();
    $pageTemplate->shouldReceive('getAttribute')->with('mTime')->andReturnNull();
    $pageTemplate->shouldReceive('getAttribute')->with('file_name')->andReturn('_pages/components');

    $this->templateEditorWidget->setTemplateValue('type', '_pages');
    $this->templateEditorWidget->setTemplateValue('file', 'components');

    request()->request->add([
        'Theme' => [
            'source' => [
                'markup' => 'new content',
                'codeSection' => 'components',
                'settings' => [
                    'components' => [],
                    'title' => 'New Title',
                    'description' => 'New Description',
                    'layout' => 'default',
                    'permalink' => '/new-permalink',
                ],
            ],
        ],
    ]);

    expect($this->templateEditorWidget->onSaveSource())->toBeNull();
});
