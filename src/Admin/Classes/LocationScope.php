<?php

namespace Igniter\Admin\Classes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class LocationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (!$model->locationableScopeEnabled())
            return;

        $builder->whereHasLocation($model->locationableGetUserLocation());
    }
}
