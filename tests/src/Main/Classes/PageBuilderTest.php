<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Classes;

use Igniter\Main\Classes\BlockManager;
use Igniter\Main\Classes\PageBuilder;
use Igniter\Main\Classes\SectionLayouts;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Models\ThemePage;
use Igniter\Tests\Main\Fixtures\TestTextBlock;
use Igniter\Tests\Main\Fixtures\TestWideFormBlock;

beforeEach(function() {
    $this->blockManager = new BlockManager;
    $this->blockManager->registerBlocks('tests-theme', [TestTextBlock::class, TestWideFormBlock::class]);
    $this->builder = new PageBuilder($this->blockManager);
    $this->theme = new Theme(testThemePath(), ['code' => 'tests-theme']);
});

it('normalizes invalid input to an empty list', function() {
    expect($this->builder->normalize($this->theme, 'invalid'))->toBe([])
        ->and($this->builder->normalize($this->theme, ['invalid']))->toBe([]);
});

it('normalizes a section layout, settings and ids', function() {
    $sections = $this->builder->normalize($this->theme, [
        ['id' => 's_valid', 'layout' => 'unknown', 'settings' => ['container' => 'full'], 'columns' => 'invalid'],
        ['id' => 'invalid id!', 'layout' => 'center-3'],
    ]);

    expect($sections)->toHaveCount(2)
        ->and($sections[0]['id'])->toBe('s_valid')
        ->and($sections[0]['layout'])->toBe(SectionLayouts::DEFAULT_LAYOUT)
        ->and($sections[0]['settings'])->toMatchArray(['container' => 'full', 'spacing' => 'md'])
        ->and($sections[0]['columns'])->toBe([['blocks' => []]])
        ->and($sections[1]['id'])->toStartWith('s_')->not->toBe('invalid id!')
        ->and($sections[1]['columns'])->toBe([['blocks' => []], ['blocks' => []], ['blocks' => []]]);
});

it('fits columns to the layout column count', function() {
    $sections = $this->builder->normalize($this->theme, [[
        'layout' => 'cols-2',
        'columns' => [
            ['blocks' => [['id' => 'b_1', 'code' => 'text']]],
            'invalid',
            ['blocks' => [['id' => 'b_2', 'code' => 'text']]],
        ],
    ]]);

    expect(array_column($sections[0]['columns'][0]['blocks'], 'id'))->toBe(['b_1'])
        ->and(array_column($sections[0]['columns'][1]['blocks'], 'id'))->toBe(['b_2']);
});

it('drops unknown, disallowed and duplicate unique blocks', function() {
    $sections = $this->builder->normalize($this->theme, [
        [
            'layout' => 'sidebar-right-3',
            'columns' => [
                ['blocks' => ['invalid', ['code' => 'missing'], ['id' => 'b_form', 'code' => 'wide-form'], ['id' => 'b_form2', 'code' => 'wide-form']]],
                ['blocks' => [['id' => 'b_narrow', 'code' => 'wide-form']]],
            ],
        ],
    ], 'checkout');

    expect(array_column($sections[0]['columns'][0]['blocks'], 'id'))->toBe(['b_form'])
        ->and($sections[0]['columns'][1]['blocks'])->toBe([]);
});

it('drops blocks not allowed on the page', function() {
    $sections = $this->builder->normalize($this->theme, [
        ['columns' => [['blocks' => [['code' => 'wide-form'], ['id' => 'b_text', 'code' => 'text']]]]],
    ], 'home');

    expect(array_column($sections[0]['columns'][0]['blocks'], 'code'))->toBe(['text']);
});

it('merges block defaults and sanitizes settings', function() {
    $sections = $this->builder->normalize($this->theme, [
        ['columns' => [['blocks' => [
            ['id' => 'b_1', 'code' => 'text', 'settings' => ['body' => 'Body', 'unknown' => 'value']],
            ['id' => 'b_2', 'code' => 'text', 'settings' => 'invalid'],
        ]]]],
    ]);

    expect($sections[0]['columns'][0]['blocks'][0]['settings'])->toBe(['title' => 'Hello', 'body' => 'Body'])
        ->and($sections[0]['columns'][0]['blocks'][1]['settings'])->toBe(['title' => 'Hello', 'body' => null]);
});

it('returns the theme default sections for a page', function() {
    $sections = $this->builder->forPage($this->theme, 'home');

    expect($sections)->toHaveCount(1)
        ->and($sections[0]['id'])->toBe('s_home')
        ->and($sections[0]['columns'][0]['blocks'][0])->toBe([
            'id' => 'b_welcome',
            'code' => 'text',
            'settings' => ['title' => 'Welcome', 'body' => null],
        ]);
});

it('returns the saved sections for a page', function() {
    ThemePage::create([
        'theme_code' => 'tests-theme',
        'page_code' => 'home',
        'sections' => [['id' => 's_saved', 'layout' => 'cols-2', 'columns' => []]],
    ]);

    $sections = $this->builder->forPage($this->theme, 'home');

    expect($sections)->toHaveCount(1)
        ->and($sections[0]['id'])->toBe('s_saved')
        ->and($sections[0]['layout'])->toBe('cols-2');
});

it('returns no sections for pages that are not buildable', function() {
    expect($this->builder->forPage($this->theme, 'not-buildable'))->toBe([]);
});

it('appends missing required blocks', function() {
    $sections = $this->builder->forPage($this->theme, 'checkout');

    expect($sections)->toHaveCount(1)
        ->and($sections[0]['layout'])->toBe('cols-1')
        ->and($sections[0]['columns'][0]['blocks'][0]['code'])->toBe('wide-form');
});

it('does not append required blocks already on the page', function() {
    ThemePage::create([
        'theme_code' => 'tests-theme',
        'page_code' => 'checkout',
        'sections' => [['id' => 's_form', 'layout' => 'cols-1', 'columns' => [['blocks' => [['id' => 'b_form', 'code' => 'wide-form']]]]]],
    ]);

    $sections = $this->builder->forPage($this->theme, 'checkout');

    expect($sections)->toHaveCount(1)
        ->and($sections[0]['columns'][0]['blocks'][0]['id'])->toBe('b_form');
});

it('skips required blocks that are not registered', function() {
    $builder = new PageBuilder(new BlockManager);

    expect($builder->forPage($this->theme, 'checkout'))->toBe([]);
});

it('resolves sections with layout information and block data', function() {
    $sections = $this->builder->resolve($this->theme, $this->builder->normalize($this->theme, [
        ['id' => 's_1', 'layout' => 'sidebar-left-3', 'columns' => [
            ['blocks' => []],
            ['blocks' => [['id' => 'b_1', 'code' => 'text', 'settings' => ['title' => 'hi']]]],
        ]],
        ['id' => 's_2', 'layout' => 'cols-5'],
    ]));

    expect($sections[0]['equal'])->toBeFalse()
        ->and($sections[0]['columns'][0]['span'])->toBe(3)
        ->and($sections[0]['columns'][1]['span'])->toBe(9)
        ->and($sections[0]['columns'][1]['blocks'][0]['data'])->toBe(['upper' => 'HI'])
        ->and($sections[1]['equal'])->toBeTrue()
        ->and($sections[1]['columns'][0]['span'])->toBeNull();
});

it('renders sections with the theme views', function() {
    $html = $this->builder->renderPage($this->theme, 'home');

    expect($html)->toContain('<section id="s_home" data-layout="cols-1">')
        ->toContain('<div data-span="12">')
        ->toContain('<h2>Welcome</h2>')
        ->toContain('<p>WELCOME</p>');
});

it('renders nothing without a theme or page code', function() {
    expect($this->builder->renderPage(null, 'home'))->toBe('')
        ->and($this->builder->renderPage($this->theme, null))->toBe('');
});

it('uses the parent theme view namespace for child themes', function() {
    $childTheme = new Theme(dirname(testThemePath(), 2).'/builder/child-theme', [
        'code' => 'tests-child-theme',
        'parent' => 'tests-theme',
    ]);

    expect($this->builder->getViewNamespace($this->theme))->toBe('tests-theme')
        ->and($this->builder->getViewNamespace($childTheme))->toBe('tests-theme');
});

it('resolves block and section html attributes', function() {
    $sections = $this->builder->resolve($this->theme, $this->builder->normalize($this->theme, [[
        'id' => 's_1',
        'layout' => 'cols-1',
        'settings' => ['html_attributes' => "data-section=\"top\"\nonclick=\"x()\""],
        'columns' => [['blocks' => [[
            'id' => 'b_1',
            'code' => 'text',
            'settings' => ['css_class' => 'promo', 'html_attributes' => 'aria-label="Intro"'],
        ]]]],
    ]]));
    $block = $sections[0]['columns'][0]['blocks'][0];

    expect($sections[0]['attributes'])->toBe(['data-section' => 'top'])
        ->and($block['attributes'])->toBe(['aria-label' => 'Intro', 'class' => 'promo'])
        ->and($block['settings'])->toMatchArray(['css_class' => 'promo'])
        ->and($block['data'])->not->toHaveKey('css_class');
});
