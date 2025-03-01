<?php

declare(strict_types=1);

namespace Igniter\Flame\Assetic\Cache;

use Igniter\Flame\Support\Facades\File;
use RuntimeException;

/**
 * A simple filesystem cache.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class FilesystemCache implements CacheInterface
{
    public function __construct(private $dir) {}

    public function has(string $key): bool
    {
        return File::exists($this->dir.'/'.$key);
    }

    public function get(string $key): ?string
    {
        $path = $this->dir.'/'.$key;

        if (!File::exists($path)) {
            throw new RuntimeException('There is no cached value for '.$key);
        }

        return File::get($path);
    }

    public function set(string $key, string $value): void
    {
        if (!File::isDirectory($this->dir) && File::makeDirectory($this->dir, 0777, true) === false) {
            throw new RuntimeException('Unable to create directory '.$this->dir);
        }

        $path = $this->dir.'/'.$key;

        if (File::put($path, $value) === false) {
            throw new RuntimeException('Unable to write file '.$path);
        }
    }

    public function remove(string $key): void
    {
        $path = $this->dir.'/'.$key;

        if (File::exists($path) && File::delete($path) === false) {
            throw new RuntimeException('Unable to remove file '.$path);
        }
    }
}
