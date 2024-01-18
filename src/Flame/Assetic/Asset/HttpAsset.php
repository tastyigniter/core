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
 * Represents an asset loaded via an HTTP request.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class HttpAsset extends BaseAsset
{
    private $sourceUrl;

    private $ignoreErrors;

    /**
     * Constructor.
     *
     * @param string $sourceUrl The source URL
     * @param array $filters An array of filters
     *
     * @throws \InvalidArgumentException If the first argument is not an URL
     */
    public function __construct(string $sourceUrl, array $filters = [], bool $ignoreErrors = false, array $vars = [])
    {
        if (str_starts_with($sourceUrl, '//')) {
            $sourceUrl = 'http:'.$sourceUrl;
        } elseif (!str_contains($sourceUrl, '://')) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid URL.', $sourceUrl));
        }

        $this->sourceUrl = $sourceUrl;
        $this->ignoreErrors = $ignoreErrors;

        [$scheme, $url] = explode('://', $sourceUrl, 2);
        [$host, $path] = explode('/', $url, 2);

        parent::__construct($filters, $scheme.'://'.$host, $path, $vars);
    }

    public function load(?FilterInterface $additionalFilter = null)
    {
        $content = @file_get_contents(
            VarUtils::resolve($this->sourceUrl, $this->getVars(), $this->getValues())
        );

        if ($content === false && !$this->ignoreErrors) {
            throw new \RuntimeException(sprintf('Unable to load asset from URL "%s"', $this->sourceUrl));
        }

        $this->doLoad($content, $additionalFilter);
    }

    public function getLastModified(): ?int
    {
        if (@file_get_contents($this->sourceUrl, false, stream_context_create(['http' => ['method' => 'HEAD']])) !== false) {
            foreach ($http_response_header as $header) {
                if (stripos($header, 'Last-Modified: ') === 0) {
                    [, $mtime] = explode(':', $header, 2);

                    return strtotime(trim($mtime));
                }
            }
        }

        return null;
    }
}
