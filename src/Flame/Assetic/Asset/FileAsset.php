<?php

declare(strict_types=1);

namespace Igniter\Flame\Assetic\Asset;

use Igniter\Flame\Assetic\Filter\FilterInterface;
use Igniter\Flame\Assetic\Util\VarUtils;
use Igniter\Flame\Support\Facades\File;
use RuntimeException;

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
            $sourceRoot = File::dirname($source);
            if ($sourcePath === null) {
                $sourcePath = File::name($source);
            }
        } elseif ($sourcePath === null) {
            if (!str_starts_with($source, $sourceRoot)) {
                throw new \InvalidArgumentException(sprintf('The source "%s" is not in the root directory "%s"', $source, $sourceRoot));
            }

            $sourcePath = substr($source, strlen($sourceRoot) + 1);
        }

        $this->source = $source;

        parent::__construct($filters, $sourceRoot, $sourcePath, $vars);
    }

    public function load(?FilterInterface $additionalFilter = null): void
    {
        $source = VarUtils::resolve($this->source, $this->getVars(), $this->getValues());

        if (!File::isFile($source)) {
            throw new RuntimeException(sprintf('The source file "%s" does not exist.', $source));
        }

        $this->doLoad(File::get($source), $additionalFilter);
    }

    public function getLastModified(): ?int
    {
        $source = VarUtils::resolve($this->source, $this->getVars(), $this->getValues());

        if (!File::isFile($source)) {
            throw new \RuntimeException(sprintf('The source file "%s" does not exist.', $source));
        }

        return File::lastModified($source);
    }
}
