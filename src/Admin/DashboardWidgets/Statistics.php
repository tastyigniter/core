<?php

namespace Igniter\Admin\DashboardWidgets;

use Carbon\Carbon;
use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Cart\Models\Order;
use Igniter\Local\Traits\LocationAwareWidget;
use Igniter\Reservation\Models\Reservation;
use Igniter\User\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

/**
 * Statistic dashboard widget.
 */
class Statistics extends BaseDashboardWidget
{
    use LocationAwareWidget;

    /** A unique alias to identify this widget. */
    protected string $defaultAlias = 'statistics';

    /**
     * Renders the widget.
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('statistics/statistics');
    }

    public function defineProperties(): array
    {
        return [
            'context' => [
                'label' => 'igniter::admin.dashboard.text_context',
                'default' => 'sale',
                'type' => 'select',
                'options' => $this->getContextOptions(),
            ],
            'range' => [
                'label' => 'igniter::admin.dashboard.text_range',
                'default' => 'week',
                'type' => 'select',
                'options' => [
                    'day' => 'lang:igniter::admin.dashboard.text_today',
                    'week' => 'lang:igniter::admin.dashboard.text_week',
                    'month' => 'lang:igniter::admin.dashboard.text_month',
                    'year' => 'lang:igniter::admin.dashboard.text_year',
                ],
            ],
        ];
    }

    public function listContext(): array
    {
        return [
            'sale' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_sale',
                'icon' => ' text-success fa fa-4x fa-line-chart',
            ],
            'lost_sale' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_lost_sale',
                'icon' => ' text-danger fa fa-4x fa-line-chart',
            ],
            'cash_payment' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_cash_payment',
                'icon' => ' text-warning fa fa-4x fa-money-bill',
            ],
            'customer' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_customer',
                'icon' => ' text-info fa fa-4x fa-users',
            ],
            'order' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_order',
                'icon' => ' text-success fa fa-4x fa-shopping-cart',
            ],
            'delivery_order' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_delivery_order',
                'icon' => ' text-primary fa fa-4x fa-truck',
            ],
            'collection_order' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_collection_order',
                'icon' => ' text-info fa fa-4x fa-shopping-bag',
            ],
            'completed_order' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_completed_order',
                'icon' => ' text-success fa fa-4x fa-receipt',
            ],
            'reserved_table' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_reserved_table',
                'icon' => ' text-primary fa fa-4x fa-table',
            ],
            'reserved_guest' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_reserved_guest',
                'icon' => ' text-primary fa fa-4x fa-table',
            ],
            'reservation' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_reservation',
                'icon' => ' text-success fa fa-4x fa-table',
            ],
            'completed_reservation' => [
                'label' => 'lang:igniter::admin.dashboard.text_total_completed_reservation',
                'icon' => ' text-success fa fa-4x fa-table',
            ],
        ];
    }

    public function getContextOptions(): array
    {
        return array_map(function ($context) {
            return array_get($context, 'label');
        }, $this->listContext());
    }

    public function getContextLabel($context): string
    {
        return array_get(array_get($this->listContext(), $context, []), 'label', '--');
    }

    public function getContextColor($context): string
    {
        return array_get(array_get($this->listContext(), $context, []), 'color', 'success');
    }

    public function getContextIcon($context): string
    {
        return array_get(array_get($this->listContext(), $context, []), 'icon', 'fa fa-4x fa-bar-chart-o');
    }

    public function loadAssets()
    {
        $this->addCss('statistics.css', 'statistics-css');
    }

    protected function prepareVars()
    {
        $this->vars['statsContext'] = $context = $this->property('context');
        $this->vars['statsLabel'] = $this->getContextLabel($context);
        $this->vars['statsIcon'] = $this->getContextIcon($context);
        $this->vars['statsCount'] = $this->callContextCountMethod($context);
    }

    protected function callContextCountMethod(string $context): int|string
    {
        $count = 0;
        $contextMethod = 'getTotal'.studly_case($context).'Sum';
        if (method_exists($this, $contextMethod)) {
            $count = $this->$contextMethod($this->property('range'), function ($range, $query) {
                $this->applyRangeQuery($query, $range);
                $this->locationApplyScope($query);
            });
        }

        return empty($count) ? 0 : $count;
    }

    protected function applyRangeQuery(Builder $query, string $range)
    {
        if ($range === 'week') {
            $start = Carbon::now()->subWeek();
        } elseif ($range === 'month') {
            $start = Carbon::now()->subMonth();
        } elseif ($range === 'year') {
            $start = Carbon::now()->startOfYear();
        } else {
            $start = Carbon::now()->today();
        }

        $query->whereBetween('created_at', [
            $start,
            Carbon::now(),
        ]);
    }

    /**
     * Return the total amount of order sales
     */
    protected function getTotalSaleSum(string $range, \Closure $callback): string
    {
        $query = Order::query();
        $query->where('status_id', '>', '0')
            ->where('status_id', '!=', setting('canceled_order_status'));

        $callback($range, $query);

        return currency_format($query->sum('order_total') ?? 0);
    }

    /**
     * Return the total amount of lost order sales
     */
    protected function getTotalLostSaleSum(string $range, \Closure $callback): string
    {
        $query = Order::query();
        $query->where(function ($query) {
            $query->where('status_id', '<=', '0');
            $query->orWhere('status_id', setting('canceled_order_status'));
        });

        $callback($range, $query);

        return currency_format($query->sum('order_total') ?? 0);
    }

    /**
     * Return the total amount of cash payment order sales
     */
    protected function getTotalCashPaymentSum(string $range, \Closure $callback): string
    {
        $query = Order::query();
        $query->where(function ($query) {
            $query->where('status_id', '>', '0');
            $query->where('status_id', '!=', setting('canceled_order_status'));
        })->where('payment', 'cod');

        $callback($range, $query);

        return currency_format($query->sum('order_total') ?? 0);
    }

    /**
     * Return the total number of customers
     */
    protected function getTotalCustomerSum(string $range, \Closure $callback): int
    {
        $query = Customer::query();
        $this->applyRangeQuery($query, $range);

        return $query->count();
    }

    /**
     * Return the total number of orders placed
     */
    protected function getTotalOrderSum(string $range, \Closure $callback): int
    {
        $query = Order::query();

        $callback($range, $query);

        return $query->count();
    }

    /**
     * Return the total number of completed orders
     */
    protected function getTotalCompletedOrderSum(string $range, \Closure $callback): int
    {
        $query = Order::query();
        $query->whereIn('status_id', setting('completed_order_status') ?? []);

        $callback($range, $query);

        return $query->count();
    }

    /**
     * Return the total number of delivery orders
     */
    protected function getTotalDeliveryOrderSum(string $range, \Closure $callback): string
    {
        $query = Order::query();
        $query->where(function ($query) {
            $query->where('order_type', '1');
            $query->orWhere('order_type', 'delivery');
        });

        $callback($range, $query);

        return currency_format($query->sum('order_total') ?? 0);
    }

    /**
     * Return the total number of collection orders
     */
    protected function getTotalCollectionOrderSum(string $range, \Closure $callback): string
    {
        $query = Order::query();
        $query->where(function ($query) {
            $query->where('order_type', '2');
            $query->orWhere('order_type', 'collection');
        });

        $callback($range, $query);

        return currency_format($query->sum('order_total') ?? 0);
    }

    /**
     * Return the total number of reserved tables
     */
    protected function getTotalReservedTableSum(string $range, \Closure $callback): int
    {
        $query = Reservation::with('tables');
        $query->where('status_id', setting('confirmed_reservation_status'));

        $callback($range, $query);

        $result = $query->get();

        $result->pluck('tables')->flatten();

        return $result->count();
    }

    /**
     * Return the total number of reserved table guests
     */
    protected function getTotalReservedGuestSum(string $range, \Closure $callback): int
    {
        $query = Reservation::query();
        $query->where('status_id', setting('confirmed_reservation_status'));

        $callback($range, $query);

        return $query->sum('guest_num') ?? 0;
    }

    /**
     * Return the total number of reservations
     */
    protected function getTotalReservationSum(string $range, \Closure $callback): int
    {
        $query = Reservation::query();
        $query->where('status_id', '!=', setting('canceled_reservation_status'));

        $callback($range, $query);

        return $query->count();
    }

    /**
     * Return the total number of completed reservations
     */
    protected function getTotalCompletedReservationSum(string $range, \Closure $callback): int
    {
        $query = Reservation::query();
        $query->where('status_id', setting('confirmed_reservation_status'));

        $callback($range, $query);

        return $query->count();
    }
}
