<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Igniter\Flame\Assetic\Filter;

use Igniter\Flame\Assetic\Asset\AssetInterface;
use Igniter\Flame\Assetic\Asset\FileAsset;
use Igniter\Flame\Assetic\Asset\HttpAsset;
use Igniter\Flame\Assetic\Factory\AssetFactory;

/**
 * Inlines imported stylesheets.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CssImportFilter extends BaseCssFilter implements DependencyExtractorInterface
{
    /**
     * Constructor.
     *
     * @param ?FilterInterface $importFilter Filter for each imported asset
     */
    public function __construct(private ?FilterInterface $importFilter = null)
    {
        $this->importFilter = $importFilter ?: new CssRewriteFilter;
    }

    public function filterLoad(AssetInterface $asset)
    {
        $importFilter = $this->importFilter;
        $sourceRoot = $asset->getSourceRoot();
        $sourcePath = $asset->getSourcePath();

        $callback = function($matches) use ($importFilter, $sourceRoot, $sourcePath) {
            if (!$matches['url'] || $sourceRoot === null) {
                return $matches[0];
            }

            $importRoot = $sourceRoot;

            if (strpos($matches['url'], '://') !== false) {
                // absolute
                [$importScheme, $tmp] = explode('://', $matches['url'], 2);
                [$importHost, $importPath] = explode('/', $tmp, 2);
                $importRoot = $importScheme.'://'.$importHost;
            } elseif (strpos($matches['url'], '//') === 0) {
                // protocol-relative
                [$importHost, $importPath] = explode('/', substr($matches['url'], 2), 2);
                $importRoot = '//'.$importHost;
            } elseif ($matches['url'][0] == '/') {
                // root-relative
                $importPath = substr($matches['url'], 1);
            } elseif ($sourcePath !== null) {
                // document-relative
                $importPath = $matches['url'];
                if ('.' != $sourceDir = dirname($sourcePath)) {
                    $importPath = $sourceDir.'/'.$importPath;
                }
            } else {
                return $matches[0];
            }

            $importSource = $importRoot.'/'.$importPath;
            if (strpos($importSource, '://') !== false || strpos($importSource, '//') === 0) {
                $import = new HttpAsset($importSource, [$importFilter], true);
            } elseif (pathinfo($importPath, PATHINFO_EXTENSION) != 'css' || !file_exists($importSource)) {
                // ignore non-css and non-existant imports
                return $matches[0];
            } else {
                $import = new FileAsset($importSource, [$importFilter], $importRoot, $importPath);
            }

            $import->setTargetPath($sourcePath);

            return $import->dump();
        };

        $content = $asset->getContent();
        $lastHash = md5($content);

        do {
            $content = $this->filterImports($content, $callback);
            $hash = md5($content);
        } while ($lastHash != $hash && $lastHash = $hash);

        $asset->setContent($content);
    }

    public function filterDump(AssetInterface $asset) {}

    public function getChildren(AssetFactory $factory, $content, $loadPath = null): array
    {
        // todo
        return [];
    }
}
