<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Igniter\Flame\Assetic\Asset;

use Igniter\Flame\Assetic\AssetManager;
use Igniter\Flame\Assetic\Filter\FilterInterface;

/**
 * A reference to an asset in the asset manager.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class AssetReference implements AssetInterface
{
    private array $filters = [];

    private bool $clone = false;

    private ?AssetInterface $asset = null;

    public function __construct(private readonly AssetManager $am, private readonly string $name) {}

    public function __clone()
    {
        $this->clone = true;

        if ($this->asset) {
            $this->asset = clone $this->asset;
        }
    }

    public function ensureFilter(FilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    public function getFilters(): array
    {
        $this->flushFilters();

        return $this->callAsset(__FUNCTION__);
    }

    public function clearFilters()
    {
        $this->filters = [];
        $this->callAsset(__FUNCTION__);
    }

    public function load(?FilterInterface $additionalFilter = null)
    {
        $this->flushFilters();

        return $this->callAsset(__FUNCTION__, [$additionalFilter]);
    }

    public function dump(?FilterInterface $additionalFilter = null): string
    {
        $this->flushFilters();

        return $this->callAsset(__FUNCTION__, [$additionalFilter]);
    }

    public function getContent(): string
    {
        return $this->callAsset(__FUNCTION__);
    }

    public function setContent($content)
    {
        $this->callAsset(__FUNCTION__, [$content]);
    }

    public function getSourceRoot(): ?string
    {
        return $this->callAsset(__FUNCTION__);
    }

    public function getSourcePath(): ?string
    {
        return $this->callAsset(__FUNCTION__);
    }

    public function getSourceDirectory(): ?string
    {
        return $this->callAsset(__FUNCTION__);
    }

    public function getTargetPath(): ?string
    {
        return $this->callAsset(__FUNCTION__);
    }

    public function setTargetPath(string $targetPath)
    {
        $this->callAsset(__FUNCTION__, [$targetPath]);
    }

    public function getLastModified(): ?int
    {
        return $this->callAsset(__FUNCTION__);
    }

    public function getVars(): array
    {
        return $this->callAsset(__FUNCTION__);
    }

    public function getValues(): array
    {
        return $this->callAsset(__FUNCTION__);
    }

    public function setValues(array $values)
    {
        $this->callAsset(__FUNCTION__, [$values]);
    }

    // private

    private function callAsset($method, $arguments = []): mixed
    {
        $asset = $this->resolve();

        return call_user_func_array([$asset, $method], $arguments);
    }

    private function flushFilters()
    {
        $asset = $this->resolve();

        while ($filter = array_shift($this->filters)) {
            $asset->ensureFilter($filter);
        }
    }

    private function resolve(): AssetInterface
    {
        if ($this->asset) {
            return $this->asset;
        }

        $asset = $this->am->get($this->name);

        if ($this->clone) {
            $asset = $this->asset = clone $asset;
        }

        return $asset;
    }
}
