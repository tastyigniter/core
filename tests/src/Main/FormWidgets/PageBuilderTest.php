<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\FormWidgets;

use Igniter\Admin\Classes\FormField;
use Igniter\Flame\Exception\FlashException;
use Igniter\Main\Classes\BlockManager;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Events\ThemeGetActiveEvent;
use Igniter\Main\FormWidgets\PageBuilder;
use Igniter\Main\Models\ThemePageLayout;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;
use Igniter\Tests\Main\Fixtures\TestTextBlock;
use Igniter\Tests\Main\Fixtures\TestWideFormBlock;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

beforeEach(function() {
    resolve(BlockManager::class)->registerBlocks('tests-theme', [TestTextBlock::class, TestWideFormBlock::class]);

    $this->controller = resolve(TestController::class);
    $this->makeWidget = function(array $config = [], mixed $value = null): PageBuilder {
        $formField = new FormField('sections', 'Sections');
        $formField->displayAs('pagebuilder');
        $formField->arrayName = 'ThemePageLayout';
        $formField->value = $value;

        return new PageBuilder($this->controller, $formField, array_merge([
            'model' => new ThemePageLayout,
            'themeCode' => 'tests-theme',
            'pageCode' => 'checkout',
        ], $config));
    };
});

it('initializes with the configured theme and page', function() {
    $widget = ($this->makeWidget)();

    expect($widget->themeCode)->toBe('tests-theme')
        ->and($widget->pageCode)->toBe('checkout')
        ->and($widget->getTheme())->toBeInstanceOf(Theme::class)
        ->and($widget->getTheme()->getName())->toBe('tests-theme');
});

it('defaults to the active theme', function() {
    Event::listen(ThemeGetActiveEvent::class, fn(): string => 'tests-theme');

    $widget = ($this->makeWidget)(['themeCode' => null]);

    expect($widget->getTheme()->getName())->toBe('tests-theme');
});

it('throws an exception when the theme is not found', function() {
    expect(fn() => ($this->makeWidget)(['themeCode' => 'missing-theme']))
        ->toThrow(FlashException::class, 'Theme [missing-theme] not found.');
});

it('loads assets', function() {
    $widget = ($this->makeWidget)();
    $widget->assetPath = [];

    Assets::shouldReceive('addCss')->once()->with('pagebuilder.css', 'pagebuilder-css');
    Assets::shouldReceive('addJs')->once()->with('pagebuilder.js', 'pagebuilder-js');

    $widget->loadAssets();
});

it('renders the editor with sections and config', function() {
    $widget = ($this->makeWidget)([], json_encode([
        ['id' => 's_1', 'layout' => 'sidebar-right-3', 'columns' => [
            ['blocks' => [['id' => 'b_form', 'code' => 'wide-form']]],
            ['blocks' => [['id' => 'b_text', 'code' => 'text', 'settings' => ['title' => 'Sidebar title']]]],
        ]],
    ]));

    $html = $widget->render();

    expect($html)->toContain('data-control="page-builder"')
        ->toContain('name="ThemePageLayout[sections]"')
        ->toContain('data-page-builder-add-section="center-3"')
        ->toContain('b_form')
        ->and($widget->vars['editorConfig']['summaries'])->toBe(['b_form' => '', 'b_text' => 'Sidebar title'])
        ->and($widget->vars['editorConfig']['blocks']['wide-form'])->toMatchArray([
            'unique' => true,
            'wide' => true,
            'required' => true,
            // Every block has the CSS class and HTML attributes fields.
            'hasFields' => true,
        ])
        ->and($widget->vars['editorConfig']['blocks']['text'])->toMatchArray([
            'required' => false,
            'hasFields' => true,
            'defaults' => ['title' => 'Hello', 'body' => null],
        ])
        ->and($widget->vars['editorConfig']['layouts']['center-3'])->toBe([
            'code' => 'center-3',
            'label' => 'Wide centre column (3/6/3)',
            'columns' => 3,
            'equal' => false,
            'spans' => [3, 6, 3],
        ]);
});

it('adds missing required blocks to the loaded sections', function() {
    $sections = ($this->makeWidget)([], [])->getSections();

    expect($sections)->toHaveCount(1)
        ->and($sections[0]['columns'][0]['blocks'][0]['code'])->toBe('wide-form');
});

it('normalizes the save value from JSON', function() {
    $widget = ($this->makeWidget)();

    $sections = $widget->getSaveValue(json_encode([
        ['id' => 's_1', 'layout' => 'cols-2', 'columns' => [
            ['blocks' => [['id' => 'b_1', 'code' => 'text', 'settings' => ['title' => 'Hi', 'unknown' => 'x']]]],
        ]],
    ]));

    expect($sections)->toHaveCount(2)
        ->and($sections[0]['columns'])->toHaveCount(2)
        ->and($sections[0]['columns'][0]['blocks'][0]['settings'])->toBe(['title' => 'Hi', 'body' => null])
        ->and($sections[1]['columns'][0]['blocks'][0]['code'])->toBe('wide-form')
        ->and($widget->getSaveValue('invalid json'))->toHaveCount(1)
        ->and($widget->getSaveValue(null))->toHaveCount(1);
});

it('loads the block settings form', function() {
    request()->request->add(['code' => 'text', 'settings' => json_encode(['title' => 'Current title'])]);

    $html = ($this->makeWidget)()->onLoadBlockForm();

    expect($html)->toContain('Edit Text')
        ->toContain('data-page-builder-form="pagebuilder::onValidateBlockForm"')
        ->toContain('data-page-builder-form-code="text"')
        ->toContain('Current title');
});

it('loads the css class and html attributes for a block without fields of its own', function() {
    request()->request->add(['code' => 'wide-form']);

    expect(($this->makeWidget)()->onLoadBlockForm())->toContain('CSS class')->toContain('HTML attributes');
});

it('throws an exception when loading an unknown block form', function() {
    request()->request->add(['code' => 'missing']);

    expect(fn() => ($this->makeWidget)()->onLoadBlockForm())
        ->toThrow(FlashException::class, 'Block [missing] is not available on this page.');
});

it('validates the block settings form', function() {
    request()->request->add([
        'code' => 'text',
        'pagebuilderSettings' => ['title' => 'New title', 'body' => '<p>Body</p>'],
    ]);

    expect(($this->makeWidget)()->onValidateBlockForm())->toBe([
        'settings' => ['title' => 'New title', 'body' => '<p>Body</p>'],
        'summary' => 'New title',
    ]);
});

it('rejects invalid block settings', function() {
    request()->request->add([
        'code' => 'text',
        'pagebuilderSettings' => ['title' => str_repeat('a', 30)],
    ]);

    expect(fn() => ($this->makeWidget)()->onValidateBlockForm())->toThrow(ValidationException::class);
});

it('loads and validates the section settings form', function() {
    request()->request->add(['settings' => json_encode(['anchor' => 'about'])]);

    $html = ($this->makeWidget)()->onLoadSectionForm();

    expect($html)->toContain('Section settings')
        ->toContain('data-page-builder-form="pagebuilder::onValidateSectionForm"')
        ->toContain('about');

    request()->request->add(['pagebuilderSettings' => ['container' => 'full', 'spacing' => 'lg', 'anchor' => 'menu']]);

    expect(($this->makeWidget)()->onValidateSectionForm()['settings'])->toMatchArray([
        'container' => 'full',
        'spacing' => 'lg',
        'anchor' => 'menu',
    ]);
});

it('builds settings forms with their own model, not the parent form model', function() {
    $parentModel = new ThemePageLayout(['theme_code' => 'tests-theme', 'page_code' => 'home']);
    $models = [];
    Event::listen('admin.form.extendFieldsBefore', function($form) use (&$models) {
        $models[$form->alias] = $form->model;
    });

    $widget = ($this->makeWidget)(['model' => $parentModel]);
    $widget->render();

    request()->request->add(['code' => 'text']);
    $widget->onLoadBlockForm();

    expect($models)->toHaveKeys(['pagebuilderAssetsForm', 'pagebuilderBlockForm'])
        ->and($models['pagebuilderAssetsForm'])->toBeInstanceOf(ThemePageLayout::class)->not->toBe($parentModel)
        ->and($models['pagebuilderBlockForm'])->not->toBe($parentModel)
        ->and($models['pagebuilderBlockForm']->exists)->toBeFalse();
});
