<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Classes;

use Igniter\Main\Classes\BaseBlock;
use Igniter\Tests\Main\Fixtures\TestTextBlock;
use Igniter\Tests\Main\Fixtures\TestWideFormBlock;
use Override;

it('merges block details with defaults', function() {
    $block = new TestTextBlock;

    expect($block->getDetails())->toBe([
        'code' => 'text',
        'name' => 'Text',
        'description' => 'A text block',
        'icon' => 'fa fa-cube',
        'unique' => false,
        'wide' => false,
        'pages' => null,
    ])
        ->and($block->getCode())->toBe('text')
        ->and($block->getName())->toBe('Text')
        ->and($block->isUnique())->toBeFalse()
        ->and($block->isWide())->toBeFalse()
        ->and($block->isAllowedOnPage('any-page'))->toBeTrue()
        ->and($block->isAllowedOnPage(null))->toBeTrue();
});

it('restricts block to pages', function() {
    $block = new TestWideFormBlock;

    expect($block->isUnique())->toBeTrue()
        ->and($block->isWide())->toBeTrue()
        ->and($block->isAllowedOnPage('checkout'))->toBeTrue()
        ->and($block->isAllowedOnPage('home'))->toBeFalse()
        ->and($block->isAllowedOnPage(null))->toBeFalse();
});

it('falls back to the code when block has no name', function() {
    $block = new class extends BaseBlock
    {
        #[Override]
        public function blockDetails(): array
        {
            return ['code' => 'nameless'];
        }
    };

    expect($block->getName())->toBe('nameless')
        ->and($block->defineFields())->toBe([])
        ->and($block->defaults())->toBe([])
        ->and($block->sanitize(['title' => 'value']))->toBe([]);
});

it('returns defaults from field definitions', function() {
    expect((new TestTextBlock)->defaults())->toBe(['title' => 'Hello', 'body' => null]);
});

it('resolves and sanitizes settings', function() {
    $block = new TestTextBlock;

    expect($block->resolve(['title' => 'hi']))->toBe(['upper' => 'HI'])
        ->and($block->sanitize(['title' => 'Hi', 'unknown' => 'value']))->toBe(['title' => 'Hi']);
});

it('returns settings as data by default', function() {
    expect((new TestWideFormBlock)->resolve(['foo' => 'bar']))->toBe(['foo' => 'bar']);
});

it('renders the theme block view', function() {
    $html = (new TestTextBlock)->render('tests-theme', [
        'id' => 'b_1',
        'code' => 'text',
        'settings' => ['title' => 'Welcome'],
        'data' => ['upper' => 'WELCOME'],
    ]);

    expect($html)->toContain('<h2>Welcome</h2>')->toContain('<p>WELCOME</p>');
});

it('returns the first text setting as summary', function() {
    $block = new TestTextBlock;

    expect($block->summary(['title' => '', 'body' => '<p>Some <b>body</b></p>']))->toBe('Some body')
        ->and($block->summary(['flag' => true, 'items' => ['a']]))->toBe('')
        ->and($block->summary(['title' => str_repeat('a', 80)]))->toBe(str_repeat('a', 60).'...');
});

it('returns validation rules from field definitions', function() {
    expect((new TestTextBlock)->validationRules())->toMatchArray(['title' => 'max:20'])
        ->and(array_keys((new TestWideFormBlock)->validationRules()))->toBe(['css_class', 'html_attributes']);
});

it('gives every block a css class and html attributes setting', function() {
    $block = new TestTextBlock;

    expect(array_keys($block->formFields()))->toContain('css_class', 'html_attributes')
        ->and($block->sanitize(['css_class' => ' promo ', 'html_attributes' => '', 'unknown' => 1]))->toMatchArray(['css_class' => 'promo'])
        ->and($block->sanitize(['html_attributes' => '']))->not->toHaveKey('html_attributes')
        ->and($block->validationRules())->toHaveKeys(['css_class', 'html_attributes'])
        ->and(BaseBlock::withoutAttributes(['css_class' => 'x', 'html_attributes' => 'y', 'title' => 'z']))->toBe(['title' => 'z']);
});
