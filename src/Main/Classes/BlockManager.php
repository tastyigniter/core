<?php

declare(strict_types=1);

namespace Igniter\Main\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\System\Classes\ExtensionManager;

/**
 * Registry of page builder blocks, scoped per theme.
 *
 * Themes register their blocks from their service provider:
 * <pre>
 *   resolve(BlockManager::class)->registerBlocks('igniter-orange', [
 *       RichText::class,
 *       ComponentBlock::make('igniter-orange::checkout', ['unique' => true, 'wide' => true]),
 *   ]);
 * </pre>
 * Extensions register blocks from registerBlocks(), keyed by theme code, or
 * by '*' for blocks available to every theme.
 *
 * Resolution order (later overrides earlier by code): '*', parent theme, theme.
 */
class BlockManager
{
    public const string ALL_THEMES = '*';

    protected bool $extensionBlocksLoaded = false;

    /** @var array<string, list<array|callable>> Registrations keyed by theme code. */
    protected array $registrations = [];

    /** @var array<string, array<string, BaseBlock>> Loaded blocks keyed by theme code then block code. */
    protected array $blocks = [];

    /**
     * Registers blocks for a theme. Accepts a list of BaseBlock instances or class
     * names, or a callable returning such a list.
     */
    public function registerBlocks(string $themeCode, array|callable $blocks): void
    {
        $this->registrations[$themeCode][] = $blocks;

        unset($this->blocks[$themeCode]);
    }

    /**
     * Returns the blocks available to a theme (including its parent theme blocks),
     * optionally filtered to those allowed on a page.
     *
     * @return array<string, BaseBlock>
     */
    public function listBlocks(Theme $theme, ?string $pageCode = null): array
    {
        $this->loadExtensionBlocks();

        $blocks = $this->loadThemeBlocks(self::ALL_THEMES);
        if ($theme->hasParent()) {
            $blocks = array_merge($blocks, $this->loadThemeBlocks((string)$theme->getParentName()));
        }

        $blocks = array_merge($blocks, $this->loadThemeBlocks($theme->getName()));

        if (is_null($pageCode)) {
            return $blocks;
        }

        return array_filter($blocks, fn(BaseBlock $block): bool => $block->isAllowedOnPage($pageCode));
    }

    public function findBlock(Theme $theme, string $code, ?string $pageCode = null): ?BaseBlock
    {
        return $this->listBlocks($theme, $pageCode)[$code] ?? null;
    }

    /**
     * Loads blocks registered by extensions through registerBlocks().
     */
    protected function loadExtensionBlocks(): void
    {
        if ($this->extensionBlocksLoaded) {
            return;
        }

        $this->extensionBlocksLoaded = true;

        $registrations = resolve(ExtensionManager::class)->getRegistrationMethodValues('registerBlocks');
        foreach ($registrations as $themeBlocks) {
            foreach ((array)$themeBlocks as $themeCode => $blocks) {
                $this->registerBlocks((string)$themeCode, $blocks);
            }
        }
    }

    /**
     * @return array<string, BaseBlock>
     */
    protected function loadThemeBlocks(string $themeCode): array
    {
        if (array_key_exists($themeCode, $this->blocks)) {
            return $this->blocks[$themeCode];
        }

        $blocks = [];
        foreach ($this->registrations[$themeCode] ?? [] as $registration) {
            if (is_callable($registration)) {
                $registration = $registration($this);
            }

            foreach ((array)$registration as $definition) {
                $block = $this->makeBlock($definition);
                $blocks[$block->getCode()] = $block;
            }
        }

        return $this->blocks[$themeCode] = $blocks;
    }

    protected function makeBlock(mixed $definition): BaseBlock
    {
        if (is_string($definition) && is_subclass_of($definition, BaseBlock::class)) {
            $definition = new $definition;
        }

        if (!$definition instanceof BaseBlock) {
            throw new SystemException(sprintf(
                'Page builder block must be an instance or class name of [%s].', BaseBlock::class,
            ));
        }

        if ($definition->getCode() === '') {
            throw new SystemException(sprintf('Page builder block [%s] has no code.', $definition::class));
        }

        return $definition;
    }
}
