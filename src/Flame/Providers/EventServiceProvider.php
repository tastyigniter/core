<?php

namespace Igniter\Flame\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as IlluminateEventServiceProvider;

abstract class EventServiceProvider extends IlluminateEventServiceProvider
{
    /**
     * The model query scopes to register.
     */
    protected array $scopes = [];

    protected array $morphMap = [];

    public function register()
    {
        parent::register();

        $this->booting(function() {
            foreach ($this->scopes as $model => $scopes) {
                $model::extend(function($model) use ($scopes) {
                    foreach ((array)$scopes as $scope) {
                        $model::addGlobalScope($scope, new $scope);
                    }
                });
            }

            Relation::enforceMorphMap($this->morphMap);
        });
    }
}
