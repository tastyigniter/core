<?php

namespace Igniter\Flame\Providers;

use Igniter\Flame\Mixins\BlueprintMixin;
use Igniter\Flame\Mixins\RouterMixin;
use Igniter\Flame\Mixins\StringMixin;
use Illuminate\Contracts\Support\DeferrableProvider;
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

        Dispatcher::macro('fire', function($event, $payload = [], $halt = false) {
            return $this->dispatch($event, $payload, $halt);
        });
    }
}
