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
 * A simple array cache
 *
 * @author Michael Mifsud <xzyfer@gmail.com>
 */
class ArrayCache implements CacheInterface
{
    private $cache = [];

    /**
     * @see CacheInterface::has()
     */
    public function has(string $key): bool
    {
        return isset($this->cache[$key]);
    }

    /**
     * @see CacheInterface::get()
     */
    public function get(string $key): ?string
    {
        if (!$this->has($key)) {
            throw new \RuntimeException('There is no cached value for '.$key);
        }

        return $this->cache[$key];
    }

    /**
     * @see CacheInterface::set()
     */
    public function set(string $key, string $value)
    {
        $this->cache[$key] = $value;
    }

    /**
     * @see CacheInterface::remove()
     */
    public function remove(string $key)
    {
        unset($this->cache[$key]);
    }
}
