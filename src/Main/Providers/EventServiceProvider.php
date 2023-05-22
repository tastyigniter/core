<?php

namespace Igniter\Main\Providers;

use Igniter\Main\Models\Customer;
use Igniter\Main\Models\Observers\CustomerObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as IlluminateEventServiceProvider;

class EventServiceProvider extends IlluminateEventServiceProvider
{
    protected $observers = [
        Customer::class => CustomerObserver::class,
    ];
}