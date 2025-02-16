<?php

declare(strict_types=1);

namespace Igniter\Flame\Database\Relations;

use Illuminate\Database\Eloquent\Model;

/**
 * Adapted from october\rain\database\relations\HasOneOrMany
 */
trait HasOneOrMany
{
    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model): void
    {
        $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());

        if (!$model->exists || $model->isDirty()) {
            $model->save();
        }

        /*
         * Use the opportunity to set the relation in memory
         */
        if ($this instanceof HasOne) {
            $this->parent->setRelation($this->relationName, $model);
        } else {
            $this->parent->reloadRelations($this->relationName);
        }
    }

    /**
     * Attach an array of models to the parent instance with deferred binding support.
     * @param array $models
     */
    public function addMany($models, $sessionKey = null): void
    {
        foreach ($models as $model) {
            $this->add($model, $sessionKey);
        }
    }

    /**
     * Removes a model from this relationship type.
     */
    public function remove(Model $model): void
    {
        $model->setAttribute($this->getForeignKeyName(), null);
        $model->save();

        /*
         * Use the opportunity to set the relation in memory
         */
        if ($this instanceof HasOne) {
            $this->parent->setRelation($this->relationName, null);
        } else {
            $this->parent->reloadRelations($this->relationName);
        }
    }

    /**
     * Get the foreign key for the relationship.
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the associated "other" key of the relationship.
     * @return string
     */
    public function getOtherKey()
    {
        return $this->localKey;
    }
}
