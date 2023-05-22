<?php

namespace Igniter\Admin\Models\Scopes;

use Igniter\Flame\Database\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LocationableScope extends Scope
{
    public function addWhereHasLocation()
    {
        return function (Builder $builder, $locationId) {
            $builder->withoutGlobalScope($this);

            $locationId = $locationId instanceof Model
                ? $locationId->getKey()
                : $locationId;

            if (!is_array($locationId)) {
                $locationId = [$locationId];
            }

            $relationName = $builder->getModel()->locationableRelationName();
            $relationObject = $builder->getModel()->getLocationableRelationObject();
            $locationModel = $relationObject->getRelated();

            if ($builder->getModel()->locationableIsSingleRelationType()) {
                $builder->whereIn($locationModel->getKeyName(), $locationId);
            } else {
                $qualifiedColumnName = $builder->getModel()->locationableIsMorphRelationType()
                    ? $relationObject->getTable().'.'.$locationModel->getKeyName()
                    : $relationObject->getParent()->getTable().'.'.$locationModel->getKeyName();

                $builder->whereHas($relationName, function ($query) use ($qualifiedColumnName, $locationId) {
                    $query->whereIn($qualifiedColumnName, $locationId);
                });
            }
        };
    }

    public function addWhereHasOrDoesntHaveLocation()
    {
        return function (Builder $builder, $locationId) {
            $builder->withoutGlobalScope($this);

            return $builder->whereHasLocation($locationId)
                ->orDoesntHave($builder->getModel()->locationableRelationName());
        };
    }
}
