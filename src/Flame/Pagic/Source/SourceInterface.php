<?php

namespace Igniter\Flame\Pagic\Source;

interface SourceInterface
{
    /**
     * Returns a single source.
     *
     * @param string $dirName
     * @param string $fileName
     * @param string $extension
     *
     * @return mixed
     */
    public function select($dirName, $fileName, $extension);

    /**
     * Returns all sources.
     *
     * @param string $dirName
     *
     * @return array
     */
    public function selectAll($dirName, array $options = []);

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
    public function insert($dirName, $fileName, $extension, $content);

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
    public function update($dirName, $fileName, $extension, $content, $oldFileName = null, $oldExtension = null);

    /**
     * Run a delete statement against the datasource.
     *
     * @param string $dirName
     * @param string $fileName
     * @param string $extension
     *
     * @return int
     */
    public function delete($dirName, $fileName, $extension);

    public function path(string $path): ?string;

    /**
     * Return the last modified date of an object
     *
     * @param string $dirName
     * @param string $fileName
     * @param string $extension
     *
     * @return int
     */
    public function lastModified($dirName, $fileName, $extension);

    /**
     * Generate a cache key unique to this source.
     *
     * @param string $name
     *
     * @return string
     */
    public function makeCacheKey($name = '');
}
