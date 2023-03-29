<?php

namespace Igniter\Flame\Pagic\Contracts;

use Exception;

interface TemplateLoader
{
    public function getFilename(string $name): ?string;

    /**
     * Gets the markup section of a template, given its name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The template source code
     *
     * @throws Exception When $name is not found
     */
    public function getMarkup(string $name): ?string;

    /**
     * Gets the source code of a template, given its name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The template source code
     *
     * @throws Exception When $name is not found
     */
    public function getContents(string $name): ?string;

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The cache key
     *
     * @throws Exception When $name is not found
     */
    public function getCacheKey(string $name): string;

    /**
     * Returns true if the template is still fresh.
     *
     * @param string $name The template name
     * @param int $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @return bool true if the template is fresh, false otherwise
     *
     * @throws Exception When $name is not found
     */
    public function isFresh(string $name, int $time): bool;

    public function exists(string $name): bool;
}
