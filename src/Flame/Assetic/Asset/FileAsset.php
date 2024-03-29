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

/**
 * Represents an asset loaded from a file.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class FileAsset extends BaseAsset
{
    private $source;

    /**
     * Constructor.
     *
     * @param string $source An absolute path
     * @param array $filters An array of filters
     * @param string $sourceRoot The source asset root directory
     * @param string $sourcePath The source asset path
     *
     * @throws \InvalidArgumentException If the supplied root doesn't match the source when guessing the path
     */
    public function __construct($source, $filters = [], $sourceRoot = null, $sourcePath = null, array $vars = [])
    {
        if ($sourceRoot === null) {
            $sourceRoot = dirname($source);
            if ($sourcePath === null) {
                $sourcePath = basename($source);
            }
        } elseif ($sourcePath === null) {
            if (strpos($source, $sourceRoot) !== 0) {
                throw new \InvalidArgumentException(sprintf('The source "%s" is not in the root directory "%s"', $source, $sourceRoot));
            }

            $sourcePath = substr($source, strlen($sourceRoot) + 1);
        }

        $this->source = $source;

        parent::__construct($filters, $sourceRoot, $sourcePath, $vars);
    }

    public function load(?FilterInterface $additionalFilter = null)
    {
        $source = VarUtils::resolve($this->source, $this->getVars(), $this->getValues());

        if (!is_file($source)) {
            throw new \RuntimeException(sprintf('The source file "%s" does not exist.', $source));
        }

        $this->doLoad(file_get_contents($source), $additionalFilter);
    }

    public function getLastModified(): ?int
    {
        $source = VarUtils::resolve($this->source, $this->getVars(), $this->getValues());

        if (!is_file($source)) {
            throw new \RuntimeException(sprintf('The source file "%s" does not exist.', $source));
        }

        return filemtime($source);
    }
}
