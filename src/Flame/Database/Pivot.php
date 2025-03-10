<?php

declare(strict_types=1);

namespace Igniter\Flame\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as ModelBase;

/**
 * @method static Builder<static>|Pivot applyFilters(array $options = [])
 * @method static Builder<static>|Pivot applySorts(array $sorts = [])
 * @method static Builder<static>|Pivot listFrontEnd(array $options = [])
 * @method static Builder<static>|Pivot newModelQuery()
 * @method static Builder<static>|Pivot newQuery()
 * @method static Builder<static>|Pivot query()
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Pivot extends Model
{
    /**
     * The parent model of the relationship.
     */
    protected ?ModelBase $parent;

    /**
     * The name of the foreign key column.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The name of the "other key" column.
     *
     * @var string
     */
    protected $otherKey;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * Create a new pivot model instance.
     *
     * @param array $attributes
     * @param string $table
     * @param bool $exists
     * @return void
     */
    public function __construct(?ModelBase $parent = null, $attributes = [], $table = null, $exists = false)
    {
        parent::__construct();

        if (is_null($parent)) {
            return;
        }

        // The pivot model is a "dynamic" model since we will set the tables dynamically
        // for the instance. This allows it work for any intermediate tables for the
        // many to many relationship that are defined by this developer's classes.
        $this->setRawAttributes($attributes, true);

        $this->setTable($table);

        $this->setConnection($parent->getConnectionName());

        // We store off the parent instance so we will access the timestamp column names
        // for the model, since the pivot model timestamps aren't easily configurable
        // from the developer's point of view. We can use the parents to get these.
        $this->parent = $parent;

        $this->exists = $exists;

        $this->timestamps = $this->hasTimestampAttributes();
    }

    /**
     * Set the keys for a save update query.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where($this->foreignKey, $this->getAttribute($this->foreignKey));

        return $query->where($this->otherKey, $this->getAttribute($this->otherKey));
    }

    /**
     * Delete the pivot model record from the database.
     *
     * @return ?bool
     */
    public function delete()
    {
        return $this->getDeleteQuery()->delete();
    }

    /**
     * Get the query builder for a delete operation on the pivot.
     *
     * @return Builder
     */
    protected function getDeleteQuery()
    {
        $foreign = $this->getAttribute($this->foreignKey);

        $query = $this->newQuery()->where($this->foreignKey, $foreign);

        return $query->where($this->otherKey, $this->getAttribute($this->otherKey));
    }

    /**
     * Get the foreign key column name.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the "other key" column name.
     *
     * @return string
     */
    public function getOtherKey()
    {
        return $this->otherKey;
    }

    /**
     * Set the key names for the pivot model instance.
     *
     * @param string $foreignKey
     * @param string $otherKey
     * @return $this
     */
    public function setPivotKeys($foreignKey, $otherKey): static
    {
        $this->foreignKey = $foreignKey;

        $this->otherKey = $otherKey;

        return $this;
    }

    /**
     * Determine if the pivot model has timestamp attributes.
     */
    public function hasTimestampAttributes(): bool
    {
        return array_key_exists($this->getCreatedAtColumn(), $this->attributes);
    }

    /**
     * Get the name of the "created at" column.
     *
     * @return string
     */
    public function getCreatedAtColumn()
    {
        return $this->parent->getCreatedAtColumn();
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        return $this->parent->getUpdatedAtColumn();
    }
}
