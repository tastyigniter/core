<?php

declare(strict_types=1);

namespace Igniter\Flame\Assetic\Asset;

use Igniter\Flame\Assetic\Cache\CacheInterface;
use Igniter\Flame\Assetic\Filter\FilterInterface;

/**
 * Caches an asset to avoid the cost of loading and dumping.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
readonly class AssetCache implements AssetInterface
{
    public function __construct(
        private AssetInterface $asset,
        private CacheInterface $cache,
    ) {}

    public function ensureFilter(FilterInterface $filter): void
    {
        $this->asset->ensureFilter($filter);
    }

    public function getFilters(): array
    {
        return $this->asset->getFilters();
    }

    public function clearFilters(): void
    {
        $this->asset->clearFilters();
    }

    public function load(?FilterInterface $additionalFilter = null): void
    {
        $cacheKey = $this->getCacheKey($this->asset, $additionalFilter, 'load');
        if ($this->cache->has($cacheKey)) {
            $this->asset->setContent($this->cache->get($cacheKey));

            return;
        }

        $this->asset->load($additionalFilter);
        $this->cache->set($cacheKey, $this->asset->getContent());
    }

    public function dump(?FilterInterface $additionalFilter = null): string
    {
        $cacheKey = $this->getCacheKey($this->asset, $additionalFilter, 'dump');
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $content = $this->asset->dump($additionalFilter);
        $this->cache->set($cacheKey, $content);

        return $content;
    }

    public function getContent(): string
    {
        return $this->asset->getContent();
    }

    public function setContent($content): void
    {
        $this->asset->setContent($content);
    }

    public function getSourceRoot(): ?string
    {
        return $this->asset->getSourceRoot();
    }

    public function getSourcePath(): ?string
    {
        return $this->asset->getSourcePath();
    }

    public function getSourceDirectory(): ?string
    {
        return $this->asset->getSourceDirectory();
    }

    public function getTargetPath(): ?string
    {
        return $this->asset->getTargetPath();
    }

    public function setTargetPath(string $targetPath): void
    {
        $this->asset->setTargetPath($targetPath);
    }

    public function getLastModified(): ?int
    {
        return $this->asset->getLastModified();
    }

    public function getVars(): array
    {
        return $this->asset->getVars();
    }

    public function setValues(array $values): void
    {
        $this->asset->setValues($values);
    }

    public function getValues(): array
    {
        return $this->asset->getValues();
    }

    /**
     * Returns a cache key for the current asset.
     *
     * The key is composed of everything but an asset's content:
     *
     *  * source root
     *  * source path
     *  * target url
     *  * last modified
     *  * filters
     *
     * @param AssetInterface $asset The asset
     * @param ?FilterInterface $additionalFilter Any additional filter being applied
     * @param string $salt Salt for the key
     *
     * @return string A key for identifying the current asset
     */
    private function getCacheKey(AssetInterface $asset, ?FilterInterface $additionalFilter = null, string $salt = ''): string
    {
        if ($additionalFilter instanceof FilterInterface) {
            $asset = clone $asset;
            $asset->ensureFilter($additionalFilter);
        }

        $cacheKey = $asset->getSourceRoot();
        $cacheKey .= $asset->getSourcePath();
        $cacheKey .= $asset->getTargetPath();
        $cacheKey .= $asset->getLastModified();

        foreach ($asset->getFilters() as $filter) {
            $cacheKey .= serialize($filter);
        }

        if ($values = $asset->getValues()) {
            asort($values);
            $cacheKey .= serialize($values);
        }

        return md5($cacheKey.$salt);
    }
}
