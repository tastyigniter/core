<?php

namespace Igniter\Flame\Database\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;

/**
 * Adapted from october\rain\database\relations\BelongsToMany
 */
class BelongsToMany extends BelongsToManyBase
{
    use DefinedConstraints;

    /**
     * @var bool This relation object is a 'count' helper.
     */
    public $countMode = false;

    /**
     * @var bool When a join is not used, don't select aliased columns.
     */
    public $orphanMode = false;

    /**
     * Create a new belongs to many relationship instance.
     *
     * @param string $table
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param string $relationName
     * @return void
     */
    public function __construct(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
    ) {
        parent::__construct(
            $query,
            $parent,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $relationName,
        );

        $this->addDefinedConstraints();
    }

    /**
     * Get the select columns for the relation query.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function shouldSelect(array $columns = ['*'])
    {
        if ($this->countMode) {
            return $this->table.'.'.$this->foreignPivotKey.' as pivot_'.$this->foreignPivotKey;
        }

        if ($columns == ['*']) {
            $columns = [$this->related->getTable().'.*'];
        }

        return array_merge($columns, $this->aliasedPivotColumns());
    }

    /**
     * Override sync() method of BelongToMany relation in order to flush the query cache.
     * @param array $ids
     * @param bool $detaching
     * @return array
     */
    public function sync($ids, $detaching = true)
    {
        parent::sync($ids, $detaching);
        $this->flushDuplicateCache();
    }

    /**
     * Override attach() method of BelongToMany relation.
     * This is necessary in order to fire 'model.relation.beforeAttach', 'model.relation.afterAttach' events
     * @param mixed $id
     * @param bool $touch
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        $insertData = $this->formatAttachRecords($this->parseIds($id), $attributes);
        $attachedIdList = array_pluck($insertData, $this->relatedPivotKey);

        if ($this->parent->fireEvent('model.relation.beforeAttach', [$this->relationName, $attachedIdList, $insertData], true) === false) {
            return;
        }

        // Here we will insert the attachment records into the pivot table. Once we have
        // inserted the records, we will touch the relationships if necessary and the
        // function will return. We can parse the IDs before inserting the records.
        $this->newPivotStatement()->insert($insertData);

        if ($touch) {
            $this->touchIfTouching();
        }

        $this->parent->fireEvent('model.relation.afterAttach', [$this->relationName, $attachedIdList, $insertData]);
    }

    /**
     * Override detach() method of BelongToMany relation.
     * This is necessary in order to fire 'model.relation.beforeDetach', 'model.relation.afterDetach' events
     * @param bool $touch
     * @return int|void
     */
    public function detach($ids = null, $touch = true)
    {
        $attachedIdList = $this->parseIds($ids);
        if (empty($attachedIdList)) {
            $attachedIdList = $this->newPivotQuery()->lists($this->relatedPivotKey);
        }

        if ($this->parent->fireEvent('model.relation.beforeDetach', [$this->relationName, $attachedIdList], true) === false) {
            return;
        }

        parent::detach($attachedIdList, $touch);

        $this->parent->fireEvent('model.relation.afterDetach', [$this->relationName, $attachedIdList]);
    }

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $pivotData = [])
    {
        $this->attach($model->getKey(), $pivotData);
        $this->parent->reloadRelations($this->relationName);
    }

    /**
     * Removes a model from this relationship type.
     */
    public function remove(Model $model)
    {
        $this->detach($model->getKey());
        $this->parent->reloadRelations($this->relationName);
    }

    /**
     * Get a paginator for the "select" statement. Complies with October Rain.
     *
     * @param int $perPage
     * @param int $currentPage
     * @param array $columns
     * @param string $pageName
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $currentPage = null, $columns = ['*'], $pageName = 'page')
    {
        $this->query->addSelect($this->shouldSelect($columns));

        $paginator = $this->query->paginate($perPage, $currentPage, $columns);

        $this->hydratePivotRelation($paginator->items());

        return $paginator;
    }

    /**
     * Create a new pivot model instance.
     *
     * @param bool $exists
     * @return \Illuminate\Database\Eloquent\Relations\Pivot
     */
    public function newPivot(array $attributes = [], $exists = false)
    {
        /*
         * October looks to the relationship parent
         */
        $pivot = $this->parent->newRelationPivot($this->relationName, $this->parent, $attributes, $this->table, $exists);

        /*
         * Laravel looks to the related model
         */
        if (empty($pivot)) {
            $pivot = $this->related->newPivot($this->parent, $attributes, $this->table, $exists);
        }

        return $pivot->setPivotKeys($this->foreignPivotKey, $this->relatedPivotKey);
    }

    /**
     * Helper for setting this relationship using various expected
     * values. For example, $model->relation = $value;
     */
    public function setSimpleValue($value)
    {
        $relationModel = $this->getRelated();

        /*
         * Nulling the relationship
         */
        if (!$value) {
            // Disassociate in memory immediately
            $this->parent->setRelation($this->relationName, $relationModel->newCollection());

            // Perform sync when the model is saved
            $this->parent->bindEventOnce('model.afterSave', function() {
                $this->detach();
            });

            return;
        }

        /*
         * Convert models to keys
         */
        if ($value instanceof Model) {
            $value = $value->getKey();
        } elseif (is_array($value)) {
            foreach ($value as $_key => $_value) {
                if ($_value instanceof Model) {
                    $value[$_key] = $_value->getKey();
                }
            }
        }

        /*
         * Convert scalar to array
         */
        if (!is_array($value) && !$value instanceof CollectionBase) {
            $value = [$value];
        }

        /*
         * Setting the relationship
         */
        $relationCollection = $value instanceof CollectionBase
            ? $value
            : $relationModel->whereIn($relationModel->getKeyName(), $value)->get();

        // Associate in memory immediately
        $this->parent->setRelation($this->relationName, $relationCollection);

        // Perform sync when the model is saved
        $this->parent->bindEventOnce('model.afterSave', function() use ($value) {
            $this->sync($value);
        });
    }

    /**
     * Helper for getting this relationship simple value,
     * generally useful with form values.
     */
    public function getSimpleValue()
    {
        $relationName = $this->relationName;

        if ($this->parent->relationLoaded($relationName)) {
            $related = $this->getRelated();

            $value = $this->parent->getRelation($relationName)->pluck($related->getKeyName())->all();
        } else {
            $value = $this->allRelatedIds()->all();
        }

        return $value;
    }

    /**
     * Get all the IDs for the related models, with deferred binding support
     *
     * @return \Illuminate\Support\Collection
     */
    public function allRelatedIds()
    {
        $related = $this->getRelated();

        $fullKey = $related->getQualifiedKeyName();

        return $this->getQuery()->select($fullKey)->pluck($related->getKeyName());
    }

    /**
     * Get the fully qualified foreign key for the relation.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->table.'.'.$this->foreignPivotKey;
    }

    /**
     * Get the fully qualified "other key" for the relation.
     *
     * @return string
     */
    public function getOtherKey()
    {
        return $this->table.'.'.$this->relatedPivotKey;
    }
}
