<?php

namespace Igniter\Flame\Pagic\Source;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Pagic\Processors\Processor;

class ChainFileSource extends AbstractSource implements SourceInterface
{
    /**
     * @var array The available source instances
     */
    protected $sources = [];

    /**
     * Create a new source instance.
     */
    public function __construct(array $sources)
    {
        $this->sources = $sources;
        $this->processor = new Processor;
    }

    /**
     * Get the source for use with CRUD operations
     *
     * @return SourceInterface
     */
    protected function getActiveSource()
    {
        return array_first($this->sources);
    }

    public function writeBlueprint(array $blueprint): bool
    {
        return $this->getActiveSource()->writeBlueprint($blueprint);
    }

    public function loadBlueprint(): array
    {
        $sourceResults = array_map(function (SourceInterface $source) {
            return array_dot($source->loadBlueprint());
        }, array_reverse($this->sources));

        return array_undot(array_merge(...$sourceResults));
    }

    /**
     * Returns a single source.
     *
     * @param string $dirName
     * @param string $fileName
     * @param string $extension
     *
     * @return mixed
     */
    public function select($dirName, $fileName, $extension)
    {
        foreach ($this->sources as $source) {
            if ($filePath = $source->select($dirName, $fileName, $extension)) {
                return $filePath;
            }
        }

        return null;
    }

    /**
     * Returns all sources.
     *
     * @param string $dirName
     *
     * @return array
     */
    public function selectAll($dirName, array $options = [])
    {
        $sourceResults = array_map(function (SourceInterface $source) use ($dirName, $options) {
            return $source->selectAll($dirName, $options);
        }, array_reverse($this->sources));

        $results = array_merge([], ...$sourceResults);

        // Remove duplicate results prioritizing results from earlier sources
        return collect($results)->keyBy('fileName')->values()->all();
    }

    /**
     * Creates a new source.
     *
     * @param string $dirName
     * @param string $fileName
     * @param string $extension
     * @param string $content
     *
     * @return bool
     */
    public function insert($dirName, $fileName, $extension, $content)
    {
        return $this->getActiveSource()->insert($dirName, $fileName, $extension, $content);
    }

    /**
     * Updates an existing source.
     *
     * @param string $dirName
     * @param string $fileName
     * @param string $extension
     * @param string $content
     * @param string $oldFileName
     * @param string $oldExtension
     *
     * @return int
     */
    public function update($dirName, $fileName, $extension, $content, $oldFileName = null, $oldExtension = null)
    {
        return $this->getActiveSource()->update($dirName, $fileName, $extension, $content, $oldFileName, $oldExtension);
    }

    /**
     * Run a delete statement against the source.
     *
     * @param string $dirName
     * @param string $fileName
     * @param string $extension
     *
     * @return int
     */
    public function delete($dirName, $fileName, $extension)
    {
        // Delete from only the active source
        $this->getActiveSource()->delete($dirName, $fileName, $extension);
    }

    /**
     * Return the last modified date of an object
     *
     * @param string $dirName
     * @param string $fileName
     * @param string $extension
     *
     * @return int
     */
    public function lastModified($dirName, $fileName, $extension)
    {
        foreach ($this->sources as $source) {
            if ($lastModified = $source->lastModified($dirName, $fileName, $extension)) {
                return $lastModified;
            }
        }
    }

    public function path(string $path): ?string
    {
        $files = resolve(Filesystem::class);
        foreach ($this->sources as $source) {
            $filePath = $source->path($path);
            if ($files->exists($filePath)) {
                return $filePath;
            }
        }

        return $path;
    }

    /**
     * Generate a cache key unique to this source.
     *
     * @param string $name
     *
     * @return string
     */
    public function makeCacheKey($name = '')
    {
        $key = '';
        foreach ($this->sources as $source) {
            $key .= $source->makeCacheKey($name).'-';
        }

        return crc32($key);
    }
}
