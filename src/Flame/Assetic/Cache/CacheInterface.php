<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Igniter\Flame\Assetic\Cache;

/**
 * Interface for a cache backend.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
interface CacheInterface
{
    /**
     * Checks if the cache has a value for a key.
     *
     * @param string $key A unique key
     *
     * @return bool Whether the cache has a value for this key
     */
    public function has(string $key): bool;

    /**
     * Returns the value for a key.
     *
     * @param string $key A unique key
     *
     * @return string|null The value in the cache
     */
    public function get(string $key): ?string;

    /**
     * Sets a value in the cache.
     *
     * @param string $key A unique key
     * @param string $value The value to cache
     */
    public function set(string $key, string $value);

    /**
     * Removes a value from the cache.
     *
     * @param string $key A unique key
     */
    public function remove(string $key);
}
