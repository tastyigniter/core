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

use Igniter\Flame\Assetic\Filter\FilterCollection;
use Igniter\Flame\Assetic\Filter\FilterInterface;

/**
 * A base abstract asset.
 *
 * The methods load() and getLastModified() are left undefined, although a
 * reusable doLoad() method is available to child classes.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
abstract class BaseAsset implements AssetInterface
{
    private FilterCollection $filters;

    private ?string $sourceDir = null;

    private $targetPath;

    private $content;

    private $loaded;

    private $values;

    public function __construct(
        array $filters,
        private readonly ?string $sourceRoot = null,
        private readonly ?string $sourcePath = null,
        private readonly array $vars = []
    ) {
        $this->filters = new FilterCollection($filters);
        if ($sourcePath && $sourceRoot) {
            $this->sourceDir = dirname("$sourceRoot/$sourcePath");
        }

        $this->values = [];
        $this->loaded = false;
    }

    public function __clone()
    {
        $this->filters = clone $this->filters;
    }

    public function ensureFilter(FilterInterface $filter)
    {
        $this->filters->ensure($filter);
    }

    public function getFilters(): array
    {
        return $this->filters->all();
    }

    public function clearFilters()
    {
        $this->filters->clear();
    }

    /**
     * Encapsulates asset loading logic.
     *
     * @param string $content The asset content
     * @param ?FilterInterface $additionalFilter An additional filter
     */
    protected function doLoad($content, ?FilterInterface $additionalFilter = null)
    {
        $filter = clone $this->filters;
        if ($additionalFilter) {
            $filter->ensure($additionalFilter);
        }

        $asset = clone $this;
        $asset->setContent($content);

        $filter->filterLoad($asset);
        $this->content = $asset->getContent();

        $this->loaded = true;
    }

    public function dump(?FilterInterface $additionalFilter = null): string
    {
        if (!$this->loaded) {
            $this->load();
        }

        $filter = clone $this->filters;
        if ($additionalFilter) {
            $filter->ensure($additionalFilter);
        }

        $asset = clone $this;
        $filter->filterDump($asset);

        return $asset->getContent();
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content)
    {
        $this->content = $content;
    }

    public function getSourceRoot(): ?string
    {
        return $this->sourceRoot;
    }

    public function getSourcePath(): ?string
    {
        return $this->sourcePath;
    }

    public function getSourceDirectory(): ?string
    {
        return $this->sourceDir;
    }

    public function getTargetPath(): ?string
    {
        return $this->targetPath;
    }

    public function setTargetPath(string $targetPath)
    {
        if ($this->vars) {
            foreach ($this->vars as $var) {
                if (!str_contains($targetPath, $var)) {
                    throw new \RuntimeException(sprintf('The asset target path "%s" must contain the variable "{%s}".', $targetPath, $var));
                }
            }
        }

        $this->targetPath = $targetPath;
    }

    public function getVars(): array
    {
        return $this->vars;
    }

    public function setValues(array $values)
    {
        foreach ($values as $var => $v) {
            if (!in_array($var, $this->vars, true)) {
                throw new \InvalidArgumentException(sprintf('The asset with source path "%s" has no variable named "%s".', $this->sourcePath, $var));
            }
        }

        $this->values = $values;
        $this->loaded = false;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
