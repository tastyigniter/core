<?php

namespace Igniter\Flame\Setting;

use Exception;
use Illuminate\Cache\Repository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DatabaseSettingStore extends SettingStore
{
    /** The database connection instance. */
    protected DatabaseManager $db;

    /** The cache instance. */
    protected Repository $cache;

    protected ?string $cacheKey = null;

    /**
     * The table to query from.
     * @var string
     */
    protected string $table = 'settings';

    /**
     * The key column name to query from.
     * @var string
     */
    protected string $keyColumn = 'item';

    /**
     * The value column name to query from.
     * @var string
     */
    protected string $valueColumn = 'value';

    /** Any query constraints that should be applied. */
    protected ?\Closure $queryConstraint = null;

    /** Any extra columns that should be added to the rows. */
    protected array $extraColumns = [];

    public function __construct(DatabaseManager $db, Repository $cache)
    {
        $this->db = $db;
        $this->cache = $cache;
    }

    /**
     * Set the table to query from.
     */
    public function setTable(string $table)
    {
        $this->table = $table;
    }

    /**
     * Set the key column name to query from.
     */
    public function setKeyColumn(string $keyColumn)
    {
        $this->keyColumn = $keyColumn;
    }

    /**
     * Set the value column name to query from.
     */
    public function setValueColumn(string $valueColumn)
    {
        $this->valueColumn = $valueColumn;
    }

    /**
     * Set the query constraint.
     */
    public function setConstraint(\Closure $callback)
    {
        $this->items = [];
        $this->loaded = false;
        $this->queryConstraint = $callback;
    }

    /**
     * Set extra columns to be added to the rows.
     */
    public function setExtraColumns(array $columns)
    {
        $this->extraColumns = $columns;
    }

    public function forget(string $key)
    {
        parent::forget($key);

        // because the database store cannot store empty arrays, remove empty
        // arrays to keep data consistent before and after saving
        $segments = explode('.', $key);
        array_pop($segments);

        while ($segments) {
            $segment = implode('.', $segments);

            // non-empty array - exit out of the loop
            if ($this->get($segment)) {
                break;
            }

            // remove the empty array and move on to the next segment
            $this->forget($segment);
            array_pop($segments);
        }
    }

    protected function write(array $data)
    {
        if (!$this->hasDatabase()) {
            return;
        }

        $keysQuery = $this->newQuery();

        $keys = $keysQuery->pluck($this->valueColumn, $this->keyColumn);
        $insertData = array_dot($data);
        $updateData = [];
        $deleteKeys = [];

        foreach ($keys as $key => $sort) {
            if (isset($insertData[$key])) {
                if ($sort != $insertData[$key]) {
                    $updateData[$key] = $insertData[$key];
                }
            } else {
                $deleteKeys[] = $key;
            }
            unset($insertData[$key]);
        }

        foreach ($updateData as $key => $value) {
            $this->newQuery()
                ->where($this->keyColumn, '=', $key)
                ->update([$this->valueColumn => $value]);
        }

        if ($insertData) {
            $this->newQuery(true)
                ->insert($this->prepareInsertData($insertData));
        }

        if ($deleteKeys) {
            $this->newQuery()
                ->whereIn($this->keyColumn, $deleteKeys)
                ->delete();
        }

        $this->flushCache();
    }

    /**
     * Transforms settings data into an array ready to be insterted into the
     * database. Call array_dot on a multidimensional array before passing it
     * into this method!
     *
     * @param array $data Call array_dot on a multidimensional array before passing it into this method!
     *
     * @return array
     */
    protected function prepareInsertData(array $data)
    {
        $dbData = [];

        if ($this->extraColumns) {
            foreach ($data as $key => $value) {
                $dbData[] = array_merge(
                    $this->extraColumns,
                    [$this->keyColumn => $key, $this->valueColumn => $this->parseInsertKeyValue($value)]
                );
            }
        } else {
            foreach ($data as $key => $value) {
                $dbData[] = [$this->keyColumn => $key, $this->valueColumn => $this->parseInsertKeyValue($value)];
            }
        }

        return $dbData;
    }

    protected function read(): array
    {
        if (!$this->hasDatabase()) {
            return [];
        }

        $collection = $this->cacheCallback(function () {
            return $this->newQuery()->get();
        });

        return $this->parseReadData($collection);
    }

    /**
     * Parse data coming from the database.
     */
    protected function parseReadData(Collection $data): array
    {
        $results = [];

        foreach ($data as $row) {
            if (is_array($row)) {
                $key = $row[$this->keyColumn];
                $value = $this->parseKeyValue($row[$this->valueColumn]);
            } elseif (is_object($row)) {
                $key = $row->{$this->keyColumn};
                $value = $this->parseKeyValue($row->{$this->valueColumn});
            } else {
                throw new \InvalidArgumentException('Expected array or object, got '.gettype($row));
            }

            Arr::set($results, $key, $value);
        }

        return $results;
    }

    /**
     * Create a new query builder instance.
     */
    protected function newQuery(bool $insert = false): Builder
    {
        $query = $this->db->table($this->table);

        if (!$insert) {
            foreach ($this->extraColumns as $key => $value) {
                $query->where($key, '=', $value);
            }
        }

        if ($this->queryConstraint !== null) {
            $callback = $this->queryConstraint;
            $callback($query, $insert);
        }

        return $query;
    }

    protected function parseKeyValue(mixed $value): mixed
    {
        $_value = @unserialize($value);
        if ($_value === false) {
            return $value;
        }

        return $_value;
    }

    protected function parseInsertKeyValue(mixed $value): mixed
    {
        return is_scalar($value) ? $value : null;
    }

    //
    // Cache
    //

    public function getCacheKey(): ?string
    {
        return $this->cacheKey;
    }

    public function setCacheKey(string $cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }

    public function flushCache()
    {
        if ($this->getCacheKey()) {
            $this->cache->forget($this->getCacheKey());
            $this->loaded = false;
        }
    }

    protected function cacheCallback(\Closure $callback): Collection
    {
        if ($cacheKey = $this->getCacheKey()) {
            return $this->cache->rememberForever($cacheKey, $callback);
        }

        return $callback();
    }

    protected function hasDatabase(): bool
    {
        try {
            return $this->db->getSchemaBuilder()->hasTable($this->table);
        } catch (Exception) {
            return false;
        }
    }
}
