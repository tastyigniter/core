<?php

namespace Igniter\Flame\Filesystem;

use DirectoryIterator;
use FilesystemIterator;
use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use League\Flysystem\Local\LocalFilesystemAdapter;
use ReflectionClass;

/**
 * File helper
 *
 * Adapted from october\rain\filesystem\Filesystem
 */
class Filesystem extends IlluminateFilesystem
{
    /** Hint path delimiter value. */
    public const HINT_PATH_DELIMITER = '::';

    /** Default file permission mask as a string ("777"). */
    public ?string $filePermissions = null;

    /** Default folder permission mask as a string ("777"). */
    public ?string $folderPermissions = null;

    /** Known path symbols and their prefixes. */
    public array $pathSymbols = [];

    /** Symlinks within base folder */
    protected ?array $symlinks = null;

    /**
     * Determine if the given path contains no files.
     */
    public function isDirectoryEmpty(string $directory): bool
    {
        if (!is_readable($directory)) {
            return true;
        }

        $handle = opendir($directory);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..') {
                closedir($handle);

                return false;
            }
        }

        closedir($handle);

        return true;
    }

    /**
     * Converts a file size in bytes to human readable format.
     */
    public function sizeToString(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        if ($bytes > 1) {
            return $bytes.' bytes';
        }

        if ($bytes == 1) {
            return $bytes.' byte';
        }

        return '0 bytes';
    }

    /**
     * Returns a public file path from an absolute one
     * eg: /home/mysite/public_html/welcome -> /welcome
     */
    public function localToPublic(string $path): ?string
    {
        $result = null;
        $publicPath = public_path();

        if (str_starts_with($path, $publicPath)) {
            $result = str_replace('\\', '/', substr($path, strlen($publicPath)));
        } else {
            /**
             * Find symlinks within base folder and work out if this path can be resolved to a symlinked directory.
             *
             * This abides by the `cms.restrictBaseDir` config and will not allow symlinks to external directories
             * if the restriction is enabled.
             */
            if ($this->symlinks === null) {
                $this->findSymlinks();
            } elseif (count($this->symlinks) > 0) {
                foreach ($this->symlinks as $source => $target) {
                    if (str_starts_with($path, $target)) {
                        $relativePath = substr($path, strlen($target));
                        $result = str_replace('\\', '/', substr($source, strlen($publicPath)).$relativePath);
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns true if the specified path is within the path of the application
     * @param string $path The path to
     * @param bool $realpath Default true, uses realpath() to resolve the provided path before checking location. Set to false if you need to check if a potentially non-existent path would be within the application path
     */
    public function isLocalPath(string $path, bool $realpath = true): bool
    {
        $base = base_path();

        if ($realpath) {
            $path = realpath($path);
        }

        return !($path === false || strncmp($path, $base, strlen($base)) !== 0);
    }

    /**
     * Returns true if the provided disk is using the "local" driver
     */
    public function isLocalDisk(FilesystemAdapter $disk): bool
    {
        return $disk->getDriver()->getAdapter() instanceof LocalFilesystemAdapter;
    }

    /**
     * Finds the path to a class
     */
    public function fromClass(string|object $className): string
    {
        $reflector = new ReflectionClass($className);

        return $reflector->getFileName();
    }

    /**
     * Determine if a file exists with case insensitivity
     * supported for the file only.
     */
    public function existsInsensitive(string $path): string|false
    {
        if ($this->exists($path)) {
            return $path;
        }

        $directoryName = dirname($path);
        $pathLower = strtolower($path);

        if (!$files = $this->glob($directoryName.'/*', GLOB_NOSORT)) {
            return false;
        }

        foreach ($files as $file) {
            if (strtolower($file) == $pathLower) {
                return $file;
            }
        }

        return false;
    }

    /**
     * Normalizes the directory separator, often used by Win systems.
     */
    public function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Converts a path using path symbol. Returns the original path if
     * no symbol is used and no default is specified.
     */
    public function symbolizePath(string $path, ?bool $default = false, bool $findExists = true): string|bool|null
    {
        if (!$symbol = $this->isPathSymbol($path)) {
            return $default === false ? $path : $default;
        }

        $_path = (string)Str::of(Str::after($path, $symbol))->after(static::HINT_PATH_DELIMITER);
        if ($_path && !Str::startsWith($_path, '/')) {
            $_path = '/'.$_path;
        }

        if (!$findExists) {
            return current($this->pathSymbols[$symbol]).$_path;
        }

        foreach ($this->pathSymbols[$symbol] as $pathSymbol) {
            if ($this->exists($pathSymbol.$_path)) {
                return $pathSymbol.$_path;
            }
        }

        return $path;
    }

    /**
     * Returns true if the path uses a symbol.
     */
    public function isPathSymbol(string $path): string|false
    {
        $symbol = Str::contains($path, static::HINT_PATH_DELIMITER)
            ? Str::before($path, static::HINT_PATH_DELIMITER)
            : substr($path, 0, 1);

        if (isset($this->pathSymbols[$symbol])) {
            return $symbol;
        }

        return false;
    }

    public function addPathSymbol(string $symbol, string $path): void
    {
        if (!array_key_exists($symbol, $this->pathSymbols) || !is_array($this->pathSymbols[$symbol])) {
            $this->pathSymbols[$symbol] = [];
        }

        array_unshift($this->pathSymbols[$symbol], $path);
    }

    /**
     * Write the contents of a file.
     * @param string $path
     * @param string $contents
     * @return int
     */
    public function put($path, $contents, $lock = false)
    {
        $result = parent::put($path, $contents, $lock);
        $this->chmod($path);

        return $result;
    }

    /**
     * Copy a file to a new location.
     * @param string $path
     * @param string $target
     * @return bool
     */
    public function copy($path, $target)
    {
        $result = parent::copy($path, $target);
        $this->chmod($target);

        return $result;
    }

    /**
     * Create a directory.
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @param bool $force
     * @return bool
     */
    public function makeDirectory($path, $mode = 0777, $recursive = false, $force = false)
    {
        if ($mask = $this->getFolderPermissions()) {
            $mode = $mask;
        }

        /*
         * Find the green leaves
         */
        if ($recursive && $mask) {
            $chmodPath = $path;
            while (true) {
                $basePath = dirname($chmodPath);
                if ($chmodPath == $basePath) {
                    break;
                }
                if ($this->isDirectory($basePath)) {
                    break;
                }
                $chmodPath = $basePath;
            }
        } else {
            $chmodPath = $path;
        }

        /*
         * Make the directory
         */
        $result = parent::makeDirectory($path, $mode, $recursive, $force);

        /*
         * Apply the permissions
         */
        if ($mask) {
            $this->chmod($chmodPath, $mask);

            if ($recursive) {
                $this->chmodRecursive($chmodPath, null, $mask);
            }
        }

        return $result;
    }

    /**
     * Modify file/folder permissions
     */
    public function chmod($path, mixed $mode = null): bool
    {
        if (!$mode) {
            $mode = $this->isDirectory($path)
                ? $this->getFolderPermissions()
                : $this->getFilePermissions();
        }

        if (!$mode) {
            return false;
        }

        return @chmod($path, $mode);
    }

    /**
     * Modify file/folder permissions recursively
     */
    public function chmodRecursive(string $path, ?string $fileMask = null, null|int|float $directoryMask = null)
    {
        if (!$fileMask) {
            $fileMask = $this->getFilePermissions();
        }

        if (!$directoryMask) {
            $directoryMask = $this->getFolderPermissions() ?: $fileMask;
        }

        if (!$fileMask) {
            return;
        }

        if (!$this->isDirectory($path)) {
            $this->chmod($path, $fileMask);

            return;
        }

        $items = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);
        foreach ($items as $item) {
            if ($item->isDir()) {
                $_path = $item->getPathname();
                $this->chmod($_path, $directoryMask);
                $this->chmodRecursive($_path, $fileMask, $directoryMask);
            } else {
                $this->chmod($item->getPathname(), $fileMask);
            }
        }
    }

    /**
     * Returns the default file permission mask to use.
     */
    public function getFilePermissions(): null|float|int
    {
        return $this->filePermissions
            ? octdec($this->filePermissions)
            : null;
    }

    /**
     * Returns the default folder permission mask to use.
     */
    public function getFolderPermissions(): null|float|int
    {
        return $this->folderPermissions
            ? octdec($this->folderPermissions)
            : null;
    }

    /**
     * Match filename against a pattern.
     */
    public function fileNameMatch(string $fileName, string $pattern): bool
    {
        if ($pattern === $fileName) {
            return true;
        }

        $regex = strtr(preg_quote($pattern, '#'), ['\*' => '.*', '\?' => '.']);

        return (bool)preg_match('#^'.$regex.'$#i', $fileName);
    }

    /**
     * Finds symlinks within the base path and provides a source => target array of symlinks.
     *
     * @return void
     */
    protected function findSymlinks()
    {
        $restrictBaseDir = Config::get('igniter-system.restrictBaseDir', true);
        $deep = Config::get('igniter-system.allowDeepSymlinks', false);
        $basePath = base_path();
        $symlinks = [];

        $iterator = function($path) use (&$iterator, &$symlinks, $basePath, $restrictBaseDir, $deep) {
            foreach (new DirectoryIterator($path) as $directory) {
                if (
                    $directory->isDir() === false
                    || $directory->isDot() === true
                ) {
                    continue;
                }
                if ($directory->isLink()) {
                    $source = $directory->getPathname();
                    $target = realpath(readlink($directory->getPathname()));
                    if (!$target) {
                        $target = realpath($directory->getPath().'/'.readlink($directory->getPathname()));

                        if (!$target) {
                            // Cannot resolve symlink
                            continue;
                        }
                    }

                    if ($restrictBaseDir && strpos($target.'/', $basePath.'/') !== 0) {
                        continue;
                    }
                    $symlinks[$source] = $target;
                    continue;
                }

                // Get subfolders if "develop.allowDeepSymlinks" is enabled.
                if ($deep) {
                    $iterator($directory->getPathname());
                }
            }
        };
        $iterator($basePath);

        $this->symlinks = $symlinks;
    }
}
