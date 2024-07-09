<?php

namespace Igniter\Flame\Pagic\Cache;

use Igniter\Flame\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use RuntimeException;

class FileSystem
{
    protected array $options = [];

    protected string $dataCacheKey = 'php-file-data';

    public function __construct(protected ?string $path = null)
    {
        $this->path = $path ?? storage_path('/igniter/cache/');
    }

    public function getCacheKey(string $name, bool $hashName = false): string
    {
        $hash = md5($name);
        $result = str_finish($this->path, '/');
        if ($hashName) {
            return $result.$hash.'.php';
        }

        $result .= substr($hash, 0, 3).'/';
        $result .= substr($hash, 3, 3).'/';

        return $result.basename($name);
    }

    public function load(string $key)
    {
        if (File::exists($key)) {
            include_once $key;
        }
    }

    public function write(string $path, string $content)
    {
        $dir = dirname($path);
        if (!File::isDirectory($dir) && !File::makeDirectory($dir, 0777, true)) {
            throw new RuntimeException(sprintf('Unable to create the cache directory (%s).', $dir));
        }

        $tmpFile = tempnam($dir, basename($path));
        if (@file_put_contents($tmpFile, $content) === false) {
            throw new RuntimeException(sprintf('Failed to write cache file "%s".', $tmpFile));
        }

        if (!@rename($tmpFile, $path)) {
            throw new RuntimeException(sprintf('Failed to write cache file "%s".', $path));
        }

        File::chmod($path);

        // Compile cached file into bytecode cache
        if (Config::get('igniter-pagic.forceBytecodeInvalidation', false)) {
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($path, true);
            } elseif (function_exists('apc_compile_file')) {
                apc_compile_file($path);
            }
        }
    }

    public function getTimestamp(string $key): int
    {
        if (!File::exists($key)) {
            return 0;
        }

        return (int)filemtime($key);
    }

    public function getCached(?string $filePath = null): ?array
    {
        $cached = Cache::get($this->dataCacheKey, false);

        if (
            $cached !== false &&
            ($cached = @unserialize(@base64_decode($cached))) !== false
        ) {
            if (is_null($filePath)) {
                return $cached;
            }

            if (array_key_exists($filePath, $cached)) {
                return $cached[$filePath];
            }
        }

        return null;
    }

    /**
     * Stores result data inside cache.
     */
    public function storeCached(string $filePath, array $cacheItem)
    {
        $cached = $this->getCached() ?: [];
        $cached[$filePath] = $cacheItem;

        Cache::put($this->dataCacheKey, base64_encode(serialize($cached)), now()->addDay());
    }
}
