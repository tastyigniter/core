<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\Main\Classes\BaseBlock;
use Igniter\Main\Classes\BlockManager;
use Igniter\Main\Classes\Theme;
use Igniter\System\Classes\ExtensionManager;
use Igniter\Tests\Main\Fixtures\TestTextBlock;
use Igniter\Tests\Main\Fixtures\TestWideFormBlock;
use Override;

beforeEach(function() {
    $this->manager = new BlockManager;
    $this->theme = new Theme(testThemePath(), ['code' => 'tests-theme']);
    $this->childTheme = new Theme(dirname(testThemePath(), 2).'/builder/child-theme', [
        'code' => 'tests-child-theme',
        'parent' => 'tests-theme',
    ]);
});

it('registers block class names and instances', function() {
    $this->manager->registerBlocks('tests-theme', [TestTextBlock::class, new TestWideFormBlock]);

    $blocks = $this->manager->listBlocks($this->theme);

    expect(array_keys($blocks))->toBe(['text', 'wide-form'])
        ->and($blocks['text'])->toBeInstanceOf(TestTextBlock::class)
        ->and($this->manager->listBlocks($this->theme))->toBe($blocks);
});

it('registers blocks from a callable', function() {
    $this->manager->registerBlocks('tests-theme', fn(BlockManager $manager): array => [TestTextBlock::class]);

    expect($this->manager->findBlock($this->theme, 'text'))->toBeInstanceOf(TestTextBlock::class)
        ->and($this->manager->findBlock($this->theme, 'missing'))->toBeNull();
});

it('filters blocks allowed on a page', function() {
    $this->manager->registerBlocks('tests-theme', [TestTextBlock::class, TestWideFormBlock::class]);

    expect(array_keys($this->manager->listBlocks($this->theme, 'home')))->toBe(['text'])
        ->and(array_keys($this->manager->listBlocks($this->theme, 'checkout')))->toBe(['text', 'wide-form'])
        ->and($this->manager->findBlock($this->theme, 'wide-form', 'home'))->toBeNull();
});

it('inherits parent theme blocks and lets the child override them', function() {
    $childText = new class extends TestTextBlock
    {
        #[Override]
        public function blockDetails(): array
        {
            return ['code' => 'text', 'name' => 'Child text'];
        }
    };

    $this->manager->registerBlocks('tests-theme', [TestTextBlock::class, TestWideFormBlock::class]);
    $this->manager->registerBlocks('tests-child-theme', [$childText]);

    $blocks = $this->manager->listBlocks($this->childTheme);

    expect(array_keys($blocks))->toBe(['text', 'wide-form'])
        ->and($blocks['text']->getName())->toBe('Child text');
});

it('reloads blocks after new registrations', function() {
    $this->manager->registerBlocks('tests-theme', [TestTextBlock::class]);
    expect($this->manager->listBlocks($this->theme))->toHaveCount(1);

    $this->manager->registerBlocks('tests-theme', [TestWideFormBlock::class]);
    expect($this->manager->listBlocks($this->theme))->toHaveCount(2);
});

it('throws an exception for invalid block definitions', function() {
    $this->manager->registerBlocks('tests-theme', [Theme::class]);

    expect(fn() => $this->manager->listBlocks($this->theme))
        ->toThrow(SystemException::class, 'Page builder block must be an instance or class name of');
});

it('throws an exception for blocks without a code', function() {
    $this->manager->registerBlocks('tests-theme', [
        new class extends BaseBlock
        {
            #[Override]
            public function blockDetails(): array
            {
                return [];
            }
        },
    ]);

    expect(fn() => $this->manager->listBlocks($this->theme))
        ->toThrow(SystemException::class, 'has no code');
});

it('loads blocks registered by extensions', function() {
    app()->instance(ExtensionManager::class, $extensionManager = mock(ExtensionManager::class));
    $extensionManager->shouldReceive('getRegistrationMethodValues')->with('registerBlocks')->once()->andReturn([
        'igniter.demo' => [
            BlockManager::ALL_THEMES => [TestTextBlock::class],
            'tests-theme' => [TestWideFormBlock::class],
        ],
    ]);

    $manager = new BlockManager;

    expect(array_keys($manager->listBlocks($this->theme)))->toBe(['text', 'wide-form'])
        ->and(array_keys($manager->listBlocks($this->childTheme)))->toBe(['text', 'wide-form'])
        ->and(array_keys($manager->listBlocks(new Theme(testThemePath(), ['code' => 'other-theme']))))->toBe(['text']);
});

it('lets theme blocks override blocks registered for all themes', function() {
    $themeText = new class extends TestTextBlock
    {
        #[Override]
        public function blockDetails(): array
        {
            return ['code' => 'text', 'name' => 'Theme text'];
        }
    };

    $this->manager->registerBlocks(BlockManager::ALL_THEMES, [TestTextBlock::class]);
    $this->manager->registerBlocks('tests-theme', [$themeText]);

    expect($this->manager->findBlock($this->theme, 'text')->getName())->toBe('Theme text');
});
