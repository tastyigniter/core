<?php

namespace Igniter\Admin\Providers;

use Igniter\Admin\EventSubscribers\StatusUpdatedSubscriber;
use Igniter\Flame\Providers\EventServiceProvider as FlameEventServiceProvider;

class EventServiceProvider extends FlameEventServiceProvider
{
    protected $observers = [
        MenuItemOption::class => MenuItemOptionObserver::class,
        Menu::class => MenuObserver::class,
        Order::class => OrderObserver::class,
        Payment::class => PaymentObserver::class,
        Reservation::class => ReservationObserver::class,
        User::class => UserObserver::class,
    ];

    protected array $scopes = [
        Category::class => CategoryScope::class,
        Location::class => LocationScope::class,
        Menu::class => MenuScope::class,
        Order::class => OrderScope::class,
        Reservation::class => ReservationScope::class,
    ];
}
