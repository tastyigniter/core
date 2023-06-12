<?php

namespace Igniter\Main\Classes;

use Igniter\Flame\Database\Attach\Manipulator;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Models\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * MediaLibrary Class
 */
class MediaLibrary
{
    protected static $cacheKey = 'main.media.contents';

    protected $storageDisk;

    protected $storagePath;

    protected $storageFolder;

    protected $ignoreNames;

    protected $ignorePatterns;

    protected $storageFolderNameLength;

    protected $config = [];

    public function initialize()
    {
        $this->config = Config::get('igniter-system.assets.media', []);

        $this->storageFolder = $this->getConfig('folder', 'media/uploads/');
        $this->storagePath = $this->getConfig('path', 'media/uploads/');

        $this->ignoreNames = $this->getConfig('ignore', []);
        $this->ignorePatterns = $this->getConfig('ignorePatterns', ['^\..*']);
    }

    public function listFolderContents($path, $methodName, $recursive = false)
    {
        $cached = Cache::get(self::$cacheKey, false);
        $cached = $cached ? @unserialize(@base64_decode($cached)) : [];

        if (!is_array($cached)) {
            $cached = [];
        }

        $cacheSuffix = $recursive ? 'recursive' : 'single';
        $cachedKey = "$cacheSuffix.$methodName.$path";

        if (array_has($cached, $cachedKey)) {
            $folderContents = array_get($cached, $cachedKey);
        } else {
            $folderContents = $this->scanFolderContents($path, $methodName, $recursive);

            $cached[$cacheSuffix][$methodName][$path] = $folderContents;
            Cache::put(
                self::$cacheKey,
                base64_encode(serialize($cached)),
                $this->getConfig('ttl', 0)
            );
        }

        return $folderContents;
    }

    public function listAllFolders($path = null, array $exclude = [])
    {
        return $this->listFolders($path, $exclude, true);
    }

    public function listFolders($path = null, array $exclude = [], $recursive = false)
    {
        if (is_null($path)) {
            $path = '/';
        }

        $path = $this->validatePath($path);

        $folders = $this->listFolderContents($path, 'directories', $recursive);

        $result = [];
        $folders = array_unique($folders, SORT_LOCALE_STRING);
        foreach ($folders as $folder) {
            if (!strlen($folder)) {
                $folder = '/';
            }

            if (starts_with($folder, $exclude)) {
                continue;
            }

            $result[] = $folder;
        }

        if ($path == '/' && !in_array('/', $result)) {
            array_unshift($result, '/');
        }

        return $result;
    }

    public function fetchFiles($path, $sortBy = [], $options = null)
    {
        if (is_string($options)) {
            $options = ['search' => $options, 'filter' => 'all'];
        }

        $files = $this->listFolderContents($path, 'files');

        $this->sortFiles($files, $sortBy);

        $this->searchFiles($files, array_get($options, 'search'));

        $this->filterFiles($files, array_get($options, 'filter'));

        return $files;
    }

    public function get($path, $stream = false)
    {
        $method = $stream ? 'readStream' : 'get';

        return $this->getStorageDisk()->$method($this->getMediaPath($path));
    }

    public function put($path, $contents)
    {
        return $this->getStorageDisk()->put($this->getMediaPath($path), $contents);
    }

    public function makeFolder($name)
    {
        return $this->getStorageDisk()->makeDirectory($this->getMediaPath($name));
    }

    public function copyFile($srcPath, $destPath)
    {
        return $this->getStorageDisk()->copy(
            $this->getMediaPath($srcPath),
            $this->getMediaPath($destPath)
        );
    }

    public function moveFile($path, $newPath)
    {
        return $this->getStorageDisk()->move(
            $this->getMediaPath($path),
            $this->getMediaPath($newPath)
        );
    }

    public function rename($path, $newPath)
    {
        return $this->getStorageDisk()->rename(
            $this->getMediaPath($path),
            $this->getMediaPath($newPath)
        );
    }

    public function deleteFiles($paths)
    {
        return $this->getStorageDisk()->delete(array_map(function ($path) {
            return $this->getMediaPath($path);
        }, (array)$paths));
    }

    public function deleteFolder($path)
    {
        return $this->getStorageDisk()->deleteDirectory($this->getMediaPath($path));
    }

    public function exists($path)
    {
        return $this->getStorageDisk()->exists($this->getMediaPath($path));
    }

    public function validatePath($path, $stripTrailingSlash = false)
    {
        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');

        return $stripTrailingSlash ? $path : '/'.$path;
    }

    public function getMediaUrl($path)
    {
        if (!starts_with($path, $this->storagePath)) {
            $path = $this->storagePath.$path;
        }

        if (starts_with($path, ['//', 'http://', 'https://'])) {
            return $path;
        }

        $path = $this->getStorageDisk()->url($path);

        return starts_with($this->storagePath, ['//', 'http://', 'https://'])
            ? $path : asset($path);
    }

    public function getMediaPath($path)
    {
        $path = ltrim($path, '/');

        return starts_with($path, $this->storageFolder)
            ? $path : $this->storageFolder.$path;
    }

    public function getUploadsPath($path)
    {
        if (starts_with($path, $this->storageFolder)) {
            return $path;
        }

        return $this->validatePath($this->storageFolder.$path, true);
    }

    public function getMediaThumb($path, $options)
    {
        $options = array_merge([
            'fit' => 'contain',
            'width' => 0,
            'height' => 0,
            'quality' => 90,
            'sharpen' => 0,
            'extension' => 'auto',
            'default' => null,
        ], $options);

        $path = $this->getMediaPath($this->validatePath($path));

        $thumbFile = $this->getMediaThumbFile($path, $options);

        if ($this->getStorageDisk()->exists($thumbFile)) {
            return $this->getStorageDisk()->url($thumbFile);
        }

        $this->ensureDirectoryExists($thumbFile);

        if (!$this->getStorageDisk()->exists($path)) {
            $path = $this->getDefaultThumbPath($thumbFile, array_get($options, 'default'));
        }

        $manipulator = Manipulator::make($path)->useSource(
            $this->getStorageDisk()
        );

        if ($manipulator->isSupported()) {
            $manipulator->manipulate(array_except($options, ['extension', 'default']));
        }

        $manipulator->save($thumbFile);

        return $this->getStorageDisk()->url($thumbFile);
    }

    public function getDefaultThumbPath($thumbFile, $default = null)
    {
        if ($default) {
            return $this->getStorageDisk()->path($this->getMediaPath($default));
        }

        $this->getStorageDisk()->put($thumbFile, Manipulator::decodedBlankImage());

        return $thumbFile;
    }

    public function getMediaRelativePath($path)
    {
        if (starts_with($path, $this->storageFolder)) {
            return str_after($path, $this->storageFolder);
        }

        return $path;
    }

    public function getConfig($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->config;
        }

        return array_get($this->config, $key, $default);
    }

    public function getAllowedExtensions()
    {
        return Settings::defaultExtensions();
    }

    public function isAllowedExtension($filename)
    {
        if (!$nameExt = pathinfo($filename, PATHINFO_EXTENSION)) {
            return $filename;
        }

        if (!in_array($nameExt, $this->getAllowedExtensions())) {
            return false;
        }

        return $nameExt;
    }

    public function resetCache()
    {
        Cache::forget(self::$cacheKey);
    }

    public function folderSize($path)
    {
        $path = $this->validatePath($path);

        $fullPath = $this->getMediaPath($path);

        $totalSize = 0;
        $files = $this->listFolderContents($fullPath, 'files');
        foreach ($files as $file) {
            $totalSize += $file->size;
        }

        return $totalSize;
    }

    protected function scanFolderContents($path, $methodName, $recursive = false)
    {
        $result = [];
        switch ($methodName) {
            case 'files':
                $files = $this->getStorageDisk()->files($this->getMediaPath($path), $recursive);
                foreach ($files as $file) {
                    if ($libraryItem = $this->initMediaItem($file, MediaItem::TYPE_FILE)) {
                        $result[] = $libraryItem;
                    }
                }
                break;
            case 'directories':
                $result = $this->getStorageDisk()->directories($this->getMediaPath($path), $recursive);
                break;
        }

        return $result;
    }

    protected function isVisible($path)
    {
        $baseName = basename($path);

        if (in_array($baseName, $this->ignoreNames)) {
            return false;
        }

        foreach ($this->ignorePatterns as $pattern) {
            if (preg_match('/'.$pattern.'/', $baseName)) {
                return false;
            }
        }

        return true;
    }

    protected function sortFiles(&$files, $sortBy)
    {
        [$by, $direction] = $sortBy;
        usort($files, function ($a, $b) use ($by) {
            switch ($by) {
                case 'name':
                    return strcasecmp($a->path, $b->path);
                case 'date':
                    if ($a->lastModified > $b->lastModified) {
                        return -1;
                    }

                    return $a->lastModified < $b->lastModified ? 1 : 0;
                case 'size':
                    if ($a->size > $b->size) {
                        return -1;
                    }

                    return $a->size < $b->size ? 1 : 0;
            }
        });

        if ($direction == 'descending') {
            $files = array_reverse($files);
        }
    }

    protected function filterFiles(&$files, $filterBy)
    {
        if (!$filterBy || $filterBy === 'all') {
            return;
        }

        $result = [];
        foreach ($files as $item) {
            if ($item->getFileType() === $filterBy) {
                $result[] = $item;
            }
        }

        $files = $result;
    }

    protected function searchFiles(&$files, $filter)
    {
        if (!$filter) {
            return;
        }

        $result = [];
        foreach ($files as $item) {
            if (str_contains($item->name, $filter)) {
                $result[] = $item;
            }
        }

        $files = $result;
    }

    protected function getThumbDirectory()
    {
        return $this->getConfig('thumbFolder', 'media/attachments/');
    }

    protected function getStorageDisk()
    {
        if ($this->storageDisk) {
            return $this->storageDisk;
        }

        return $this->storageDisk = Storage::disk(
            $this->getConfig('disk', 'local')
        );
    }

    protected function initMediaItem($path, $itemType)
    {
        $relativePath = $this->getMediaRelativePath($path);

        if (!$this->isVisible($relativePath)) {
            return;
        }

        $lastModified = $itemType == MediaItem::TYPE_FILE
            ? $this->getStorageDisk()->lastModified($path)
            : null;

        $size = $itemType == MediaItem::TYPE_FILE
            ? $this->getStorageDisk()->size($path)
            : null;

        $publicUrl = $this->getMediaUrl($path);

        return new MediaItem($relativePath, $size, $lastModified, $itemType, $publicUrl);
    }

    protected function pathMatchesSearch($path, $words)
    {
        $path = Str::lower($path);

        foreach ($words as $word) {
            $word = trim($word);
            if (!strlen($word)) {
                continue;
            }

            if (!Str::contains($path, $word)) {
                return false;
            }
        }

        return true;
    }

    protected function getMediaThumbFile($filePath, $options)
    {
        $itemSignature = md5($filePath.serialize($options)).'_'.@File::lastModified($filePath);
        $thumbFilename = 'thumb_'.
            $itemSignature.'_'.
            array_get($options, 'width').'x'.
            array_get($options, 'height').'_'.
            array_get($options, 'fit', 'auto').'.'.
            File::extension($filePath);

        $partition = implode('/', array_slice(str_split($itemSignature, 3), 0, 3)).'/';

        return $this->getThumbDirectory().$partition.$thumbFilename;
    }

    protected function ensureDirectoryExists($path)
    {
        if ($this->getStorageDisk()->exists($directory = dirname($path))) {
            return;
        }

        $this->getStorageDisk()->makeDirectory($directory);
    }
}
