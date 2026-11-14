<?php

declare(strict_types=1);

namespace Igniter\System\Classes\MailBuilder;

use Igniter\Flame\Exception\SystemException;
use Igniter\System\Classes\ExtensionManager;

/**
 * Registry of mail builder blocks.
 *
 * Extensions register blocks from registerMailBlocks(), or with a callback:
 * <pre>
 *   resolve(MailBlockManager::class)->registerCallback(function (MailBlockManager $manager) {
 *       $manager->registerBlocks([OrderSummary::class]);
 *       $manager->extendBlock('button', fn (BaseMailBlock $block) => $block->addFields([...]));
 *       $manager->removeBlock('spacer');
 *   });
 * </pre>
 */
class MailBlockManager
{
    protected bool $loaded = false;

    /** @var list<array|callable> */
    protected array $registrations = [];

    /** @var list<callable> */
    protected array $callbacks = [];

    /** @var array<string, list<callable>> */
    protected array $extenders = [];

    /** @var array<string, bool> */
    protected array $removed = [];

    /** @var array<string, BaseMailBlock>|null */
    protected ?array $blocks = null;

    /**
     * Registers blocks: a list of BaseMailBlock instances or class names, or a
     * callable returning such a list.
     */
    public function registerBlocks(array|callable $blocks): void
    {
        $this->registrations[] = $blocks;
        $this->blocks = null;
    }

    public function registerBlock(BaseMailBlock|string $block): void
    {
        $this->registerBlocks([$block]);
    }

    /**
     * Registers a callback that registers, extends or removes blocks. The
     * manager is passed to the callback.
     */
    public function registerCallback(callable $callback): void
    {
        $this->callbacks[] = $callback;
        $this->blocks = null;
    }

    public function removeBlock(string $code): void
    {
        $this->removed[$code] = true;
        $this->blocks = null;
    }

    /**
     * Extends a block once it is loaded, for example to add fields.
     *
     * @param callable(BaseMailBlock): void $callback
     */
    public function extendBlock(string $code, callable $callback): void
    {
        $this->extenders[$code][] = $callback;
        $this->blocks = null;
    }

    /**
     * Returns the registered blocks, optionally only those offered for a context.
     *
     * @return array<string, BaseMailBlock>
     */
    public function listBlocks(?CompileContext $context = null): array
    {
        $blocks = $this->loadBlocks();

        if (is_null($context)) {
            return $blocks;
        }

        return array_filter($blocks, fn(BaseMailBlock $block): bool => $block->supportsMode($context->mode)
            && $block->appliesTo($context));
    }

    public function findBlock(string $code): ?BaseMailBlock
    {
        return $this->loadBlocks()[$code] ?? null;
    }

    /**
     * Script files of every block offered for a context.
     *
     * @return list<string>
     */
    public function listAssets(CompileContext $context): array
    {
        $assets = [];
        foreach ($this->listBlocks($context) as $block) {
            $assets = array_merge($assets, $block->assets());
        }

        return array_values(array_unique($assets));
    }

    /**
     * @return array<string, BaseMailBlock>
     */
    protected function loadBlocks(): array
    {
        if (!is_null($this->blocks)) {
            return $this->blocks;
        }

        $this->loadRegistrations();

        $blocks = [];
        foreach ($this->registrations as $registration) {
            if (is_callable($registration)) {
                $registration = $registration($this);
            }

            foreach ((array)$registration as $definition) {
                $block = $this->makeBlock($definition);
                $code = $block->getCode();

                if (isset($blocks[$code])) {
                    throw new SystemException(sprintf(
                        'Mail block [%s] is already registered by [%s]. Use extendBlock() to change it.', $code, $blocks[$code]::class,
                    ));
                }

                $blocks[$code] = $block;
            }
        }

        foreach ($this->extenders as $code => $callbacks) {
            foreach (isset($blocks[$code]) ? $callbacks : [] as $callback) {
                $callback($blocks[$code]);
            }
        }

        return $this->blocks = array_diff_key($blocks, $this->removed);
    }

    protected function loadRegistrations(): void
    {
        // Callbacks run once, in the order they were registered
        while ($callback = array_shift($this->callbacks)) {
            $callback($this);
        }

        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        foreach (resolve(ExtensionManager::class)->getRegistrationMethodValues('registerMailBlocks') as $blocks) {
            if (is_array($blocks) && $blocks !== []) {
                $this->registrations[] = $blocks;
            }
        }
    }

    protected function makeBlock(mixed $definition): BaseMailBlock
    {
        if (is_string($definition) && is_subclass_of($definition, BaseMailBlock::class)) {
            $definition = new $definition;
        }

        if (!$definition instanceof BaseMailBlock) {
            throw new SystemException(sprintf(
                'Mail block must be an instance or class name of [%s].', BaseMailBlock::class,
            ));
        }

        if (!preg_match('/^[\w.:-]+$/', $definition->getCode())) {
            throw new SystemException(sprintf('Mail block [%s] has no valid code.', $definition::class));
        }

        return $definition;
    }
}
