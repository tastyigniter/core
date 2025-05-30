<?php

declare(strict_types=1);

namespace Igniter\Flame\Assetic\Asset;

use Igniter\Flame\Assetic\Asset\Iterator\AssetCollectionFilterIterator;
use Igniter\Flame\Assetic\Asset\Iterator\AssetCollectionIterator;
use Igniter\Flame\Assetic\Filter\FilterCollection;
use Igniter\Flame\Assetic\Filter\FilterInterface;
use InvalidArgumentException;
use IteratorAggregate;
use RecursiveIteratorIterator;
use SplObjectStorage;
use Traversable;

/**
 * A collection of assets.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class AssetCollection implements AssetCollectionInterface, IteratorAggregate
{
    private FilterCollection $filters;

    private ?string $targetPath = null;

    private ?string $content = null;

    private SplObjectStorage $clones;

    private array $values = [];

    public function __construct(
        private array $assets,
        array $filters = [],
        private readonly ?string $sourceRoot = null,
        private readonly array $vars = [],
    ) {
        $this->filters = new FilterCollection($filters);
        $this->clones = new SplObjectStorage;
    }

    public function __clone()
    {
        $this->filters = clone $this->filters;
        $this->clones = new SplObjectStorage;
    }

    public function all(): array
    {
        return $this->assets;
    }

    public function add(AssetInterface $asset): void
    {
        $this->assets[] = $asset;
    }

    public function removeLeaf(AssetInterface $leaf, bool $graceful = false): bool
    {
        foreach ($this->assets as $i => $asset) {
            $clone = $this->clones[$asset] ?? null;
            if (in_array($leaf, [$asset, $clone], true)) {
                unset($this->clones[$asset], $this->assets[$i]);

                return true;
            }

            if ($asset instanceof AssetCollectionInterface && $asset->removeLeaf($leaf, true)) {
                return true;
            }
        }

        if ($graceful) {
            return false;
        }

        throw new InvalidArgumentException('Leaf not found.');
    }

    public function replaceLeaf(AssetInterface $needle, AssetInterface $replacement, bool $graceful = false): bool
    {
        foreach ($this->assets as $i => $asset) {
            $clone = $this->clones[$asset] ?? null;
            if (in_array($needle, [$asset, $clone], true)) {
                unset($this->clones[$asset]);
                $this->assets[$i] = $replacement;

                return true;
            }

            if ($asset instanceof AssetCollectionInterface && $asset->replaceLeaf($needle, $replacement, true)) {
                return true;
            }
        }

        if ($graceful) {
            return false;
        }

        throw new InvalidArgumentException('Leaf not found.');
    }

    public function ensureFilter(FilterInterface $filter): void
    {
        $this->filters->ensure($filter);
    }

    public function getFilters(): array
    {
        return $this->filters->all();
    }

    public function clearFilters(): void
    {
        $this->filters->clear();
        $this->clones = new SplObjectStorage;
    }

    public function load(?FilterInterface $additionalFilter = null): void
    {
        // loop through leaves and load each asset
        $parts = [];
        foreach ($this as $asset) {
            $asset->load($additionalFilter);
            $parts[] = $asset->getContent();
        }

        $this->content = implode("\n", $parts);
    }

    public function dump(?FilterInterface $additionalFilter = null): string
    {
        // loop through leaves and dump each asset
        $parts = [];
        foreach ($this as $asset) {
            $parts[] = $asset->dump($additionalFilter);
        }

        return implode("\n", $parts);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getSourceRoot(): ?string
    {
        return $this->sourceRoot;
    }

    public function getSourcePath(): ?string
    {
        return null;
    }

    public function getSourceDirectory(): ?string
    {
        return null;
    }

    public function getTargetPath(): ?string
    {
        return $this->targetPath;
    }

    public function setTargetPath(string $targetPath): void
    {
        $this->targetPath = $targetPath;
    }

    /**
     * Returns the highest last-modified value of all assets in the current collection.
     *
     * @return int|null A UNIX timestamp
     */
    public function getLastModified(): ?int
    {
        if ($this->assets === []) {
            return 0;
        }

        $mtime = 0;
        foreach ($this as $asset) {
            $assetMtime = $asset->getLastModified();
            if ($assetMtime > $mtime) {
                $mtime = $assetMtime;
            }
        }

        return $mtime;
    }

    /**
     * Returns an iterator for looping recursively over unique leaves.
     */
    public function getIterator(): Traversable
    {
        return new RecursiveIteratorIterator(new AssetCollectionFilterIterator(new AssetCollectionIterator($this, $this->clones)));
    }

    public function getVars(): array
    {
        return $this->vars;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;

        foreach ($this as $asset) {
            $asset->setValues(array_intersect_key($values, array_flip($asset->getVars())));
        }
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
