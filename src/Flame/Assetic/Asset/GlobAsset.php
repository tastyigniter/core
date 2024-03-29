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

use Igniter\Flame\Assetic\Filter\FilterInterface;
use Igniter\Flame\Assetic\Util\VarUtils;
use Traversable;

/**
 * A collection of assets loaded by glob.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class GlobAsset extends AssetCollection
{
    private bool $initialized;

    /**
     * Constructor.
     *
     * @param string|array $globs A single glob path or array of paths
     * @param array $filters An array of filters
     * @param ?string $root The root directory
     */
    public function __construct(private string|array $globs, array $filters = [], $root = null, array $vars = [])
    {
        $this->globs = (array)$globs;
        $this->initialized = false;

        parent::__construct([], $filters, $root, $vars);
    }

    public function all(): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::all();
    }

    public function load(?FilterInterface $additionalFilter = null)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        parent::load($additionalFilter);
    }

    public function dump(?FilterInterface $additionalFilter = null): string
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::dump($additionalFilter);
    }

    public function getLastModified(): ?int
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::getLastModified();
    }

    public function getIterator(): Traversable
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::getIterator();
    }

    public function setValues(array $values)
    {
        parent::setValues($values);
        $this->initialized = false;
    }

    /**
     * Initializes the collection based on the glob(s) passed in.
     */
    private function initialize()
    {
        foreach ($this->globs as $glob) {
            $glob = VarUtils::resolve($glob, $this->getVars(), $this->getValues());

            if (false !== $paths = glob($glob)) {
                foreach ($paths as $path) {
                    if (is_file($path)) {
                        $asset = new FileAsset($path, [], $this->getSourceRoot(), null, $this->getVars());
                        $asset->setValues($this->getValues());
                        $this->add($asset);
                    }
                }
            }
        }

        $this->initialized = true;
    }
}
