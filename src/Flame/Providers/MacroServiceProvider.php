<?php

namespace Igniter\Flame\Providers;

use Igniter\Flame\Mixins\BlueprintMixin;
use Igniter\Flame\Mixins\RouterMixin;
use Igniter\Flame\Mixins\StringMixin;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class MacroServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
        Str::mixin(new StringMixin);

        Router::mixin(new RouterMixin);

        Blueprint::mixin(new BlueprintMixin);

        QueryBuilder::macro('toRawSql', function() {
            return array_reduce($this->getBindings(), function($sql, $binding) {
                return preg_replace('/\?/', is_numeric($binding) ? $binding : "'".$binding."'", $sql, 1);
            }, $this->toSql());
        });

        EloquentBuilder::macro('toRawSql', function() {
            return $this->getQuery()->toRawSql();
        });

        Dispatcher::macro('fire', function($event, $payload = [], $halt = false) {
            return $this->dispatch($event, $payload, $halt);
        });
    }

    public function provides()
    {
        return [
            'mail.manager',
            'router',
            'events',
        ];
    }
}
