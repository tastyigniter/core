<?php

namespace Igniter\Flame\Pagic\Source;

interface SourceInterface
{
    /**
     * Returns a single source.
     */
    public function select(string $dirName, string $fileName, string $extension): ?array;

    /**
     * Returns all sources.
     */
    public function selectAll(string $dirName, array $options = []): array;

    /**
     * Creates a new source.
     */
    public function insert(string $dirName, string $fileName, string $extension, string $content): bool;

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
    ): int;

    /**
     * Run a delete statement against the datasource.
     */
    public function delete(string $dirName, string $fileName, string $extension): int;

    public function path(string $path): ?string;

    /**
     * Return the last modified date of an object
     */
    public function lastModified(string $dirName, string $fileName, string $extension): ?int;

    /**
     * Generate a cache key unique to this source.
     */
    public function makeCacheKey(string $name = ''): int;
}
