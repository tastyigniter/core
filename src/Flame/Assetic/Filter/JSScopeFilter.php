<?php

namespace Igniter\Flame\Assetic\Filter;

use Igniter\Flame\Assetic\Asset\AssetInterface;

/**
 * Filters assets by wrapping them in a self executing anonymous function.
 */
class JSScopeFilter implements FilterInterface
{
    public function filterLoad(AssetInterface $asset) {}

    public function filterDump(AssetInterface $asset)
    {
        $asset->setContent(sprintf("(function() {\n%s\n})();", $asset->getContent()));
    }
}
