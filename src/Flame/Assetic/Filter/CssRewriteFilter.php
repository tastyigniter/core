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

/**
 * Fixes relative CSS urls.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CssRewriteFilter extends BaseCssFilter
{
    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
        $sourceBase = $asset->getSourceRoot();
        $sourcePath = $asset->getSourcePath();
        $targetPath = $asset->getTargetPath();

        if ($sourcePath === null || $targetPath === null || $sourcePath == $targetPath) {
            return;
        }

        // learn how to get from the target back to the source
        if (strpos($sourceBase, '://') !== false) {
            [$scheme, $url] = explode('://', $sourceBase.'/'.$sourcePath, 2);
            [$host, $path] = explode('/', $url, 2);

            $host = $scheme.'://'.$host.'/';
            $path = strpos($path, '/') === false ? '' : dirname($path);
            $path .= '/';
        } else {
            // assume source and target are on the same host
            $host = '';

            // pop entries off the target until it fits in the source
            if (dirname($sourcePath) == '.') {
                $path = str_repeat('../', substr_count($targetPath, '/'));
            } elseif ('.' == $targetDir = dirname($targetPath)) {
                $path = dirname($sourcePath).'/';
            } else {
                $path = '';
                while (strpos($sourcePath, $targetDir) !== 0) {
                    if (false !== $pos = strrpos($targetDir, '/')) {
                        $targetDir = substr($targetDir, 0, $pos);
                        $path .= '../';
                    } else {
                        $targetDir = '';
                        $path .= '../';
                        break;
                    }
                }
                $path .= ltrim(substr(dirname($sourcePath).'/', strlen($targetDir)), '/');
            }
        }

        $content = $this->filterReferences($asset->getContent(), function ($matches) use ($host, $path) {
            if (strpos($matches['url'], '://') !== false || strpos($matches['url'], '//') === 0 || strpos($matches['url'], 'data:') === 0) {
                // absolute or protocol-relative or data uri
                return $matches[0];
            }

            if (isset($matches['url'][0]) && $matches['url'][0] == '/') {
                // root relative
                return str_replace($matches['url'], $host.$matches['url'], $matches[0]);
            }

            // document relative
            $url = $matches['url'];
            while (strpos($url, '../') === 0 && substr_count($path, '/') >= 2) {
                $path = substr($path, 0, strrpos(rtrim($path, '/'), '/') + 1);
                $url = substr($url, 3);
            }

            $parts = [];
            foreach (explode('/', $host.$path.$url) as $part) {
                if ($part === '..' && count($parts) && end($parts) !== '..') {
                    array_pop($parts);
                } else {
                    $parts[] = $part;
                }
            }

            return str_replace($matches['url'], implode('/', $parts), $matches[0]);
        });

        $asset->setContent($content);
    }
}
