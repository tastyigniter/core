<?php

namespace Igniter\Flame\Database\Relations;

use Illuminate\Database\Eloquent\Model;

/**
 * Adapted from october\rain\database\relations\MorphOneOrMany
 */
trait MorphOneOrMany
{
    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model)
    {
        $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
        $model->setAttribute($this->getMorphType(), $this->morphClass);
        $model->save();

        /*
         * Use the opportunity to set the relation in memory
         */
        if ($this instanceof MorphOne) {
            $this->parent->setRelation($this->relationName, $model);
        } else {
            $this->parent->reloadRelations($this->relationName);
        }
    }

    /**
     * Removes a model from this relationship type.
     */
    public function remove(Model $model)
    {
        $options = $this->parent->getRelationDefinition($this->relationName);

        if (array_get($options, 'delete', false)) {
            $model->delete();
        } else {
            /*
             * Make this model an orphan ;~(
             */
            $model->setAttribute($this->getForeignKeyName(), null);
            $model->setAttribute($this->getMorphType(), null);
            $model->save();
        }

        /*
         * Use the opportunity to set the relation in memory
         */
        if ($this instanceof MorphOne) {
            $this->parent->setRelation($this->relationName, null);
        } else {
            $this->parent->reloadRelations($this->relationName);
        }
    }
}
