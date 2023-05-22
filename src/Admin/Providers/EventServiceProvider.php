<?php

namespace Igniter\Admin\Providers;

use Igniter\Admin\Models\Category;
use Igniter\Admin\Models\Location;
use Igniter\Admin\Models\Menu;
use Igniter\Admin\Models\MenuItemOption;
use Igniter\Admin\Models\Observers\MenuItemOptionObserver;
use Igniter\Admin\Models\Observers\MenuObserver;
use Igniter\Admin\Models\Observers\OrderObserver;
use Igniter\Admin\Models\Observers\PaymentObserver;
use Igniter\Admin\Models\Observers\ReservationObserver;
use Igniter\Admin\Models\Observers\UserObserver;
use Igniter\Admin\Models\Order;
use Igniter\Admin\Models\Payment;
use Igniter\Admin\Models\Reservation;
use Igniter\Admin\Models\Scopes\CategoryScope;
use Igniter\Admin\Models\Scopes\LocationScope;
use Igniter\Admin\Models\Scopes\MenuScope;
use Igniter\Admin\Models\Scopes\OrderScope;
use Igniter\Admin\Models\Scopes\ReservationScope;
use Igniter\Admin\Models\User;
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