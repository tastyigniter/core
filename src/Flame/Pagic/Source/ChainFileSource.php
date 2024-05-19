<?php

namespace Igniter\Flame\Pagic\Source;

use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Pagic\Processors\Processor;

class ChainFileSource extends AbstractSource implements SourceInterface
{
    /**
     * Create a new source instance.
     */
    public function __construct(protected array $sources)
    {
        $this->processor = new Processor;
    }

    /**
     * Get the source for use with CRUD operations
     */
    protected function getActiveSource(): SourceInterface
    {
        return array_first($this->sources);
    }

    /**
     * Returns a single source.
     */
    public function select(string $dirName, string $fileName, string $extension): ?array
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
     */
    public function selectAll(string $dirName, array $options = []): array
    {
        $sourceResults = array_map(function(SourceInterface $source) use ($dirName, $options) {
            return $source->selectAll($dirName, $options);
        }, array_reverse($this->sources));

        $results = array_merge([], ...$sourceResults);

        // Remove duplicate results prioritizing results from earlier sources
        return collect($results)->keyBy('fileName')->values()->all();
    }

    /**
     * Creates a new source.
     */
    public function insert(string $dirName, string $fileName, string $extension, string $content): bool
    {
        return $this->getActiveSource()->insert($dirName, $fileName, $extension, $content);
    }

    /**
     * Updates an existing source.
     */
    public function update(
        string $dirName,
        string $fileName,
        string $extension,
        string $content,
        ?string $oldFileName = null,
        ?string $oldExtension = null
    ): int {
        return $this->getActiveSource()->update($dirName, $fileName, $extension, $content, $oldFileName, $oldExtension);
    }

    /**
     * Run a delete statement against the source.
     */
    public function delete(string $dirName, string $fileName, string $extension): int
    {
        // Delete from only the active source
        return $this->getActiveSource()->delete($dirName, $fileName, $extension);
    }

    /**
     * Return the last modified date of an object
     */
    public function lastModified(string $dirName, string $fileName, string $extension): ?int
    {
        foreach ($this->sources as $source) {
            if ($lastModified = $source->lastModified($dirName, $fileName, $extension)) {
                return $lastModified;
            }
        }

        return null;
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
     */
    public function makeCacheKey(string $name = ''): int
    {
        $key = '';
        foreach ($this->sources as $source) {
            $key .= $source->makeCacheKey($name).'-';
        }

        return crc32($key);
    }
}
