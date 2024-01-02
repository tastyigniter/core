<?php

namespace Igniter\Admin\DashboardWidgets;

use Carbon\Carbon;
use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Cart\Models\Order;
use Igniter\Local\Traits\LocationAwareWidget;
use Igniter\Reservation\Models\Reservation;
use Igniter\User\Models\Customer;

/**
 * Statistic dashboard widget.
 */
class Statistics extends BaseDashboardWidget
{
    use LocationAwareWidget;

    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'statistics';

    /**
     * Renders the widget.
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('statistics/statistics');
    }

    public function defineProperties()
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

    public function listContext()
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

    public function getContextOptions()
    {
        return array_map(function ($context) {
            return array_get($context, 'label');
        }, $this->listContext());
    }

    public function getContextLabel($context)
    {
        return array_get(array_get($this->listContext(), $context, []), 'label', '--');
    }

    public function getContextColor($context)
    {
        return array_get(array_get($this->listContext(), $context, []), 'color', 'success');
    }

    public function getContextIcon($context)
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

    protected function callContextCountMethod($context)
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

    protected function applyRangeQuery($query, $range)
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
     *
     * @return string
     */
    protected function getTotalSaleSum($range, $callback)
    {
        $query = Order::query();
        $query->where('status_id', '>', '0')
            ->where('status_id', '!=', setting('canceled_order_status'));

        $callback($range, $query);

        return currency_format($query->sum('order_total') ?? 0);
    }

    /**
     * Return the total amount of lost order sales
     *
     * @return string
     */
    protected function getTotalLostSaleSum($range, $callback)
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
     *
     * @return string
     */
    protected function getTotalCashPaymentSum($range, $callback)
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
     *
     * @return int
     */
    protected function getTotalCustomerSum($range, $callback)
    {
        $query = Customer::query();
        $this->applyRangeQuery($query, $range);

        return $query->count();
    }

    /**
     * Return the total number of orders placed
     *
     * @return int
     */
    protected function getTotalOrderSum($range, $callback)
    {
        $query = Order::query();

        $callback($range, $query);

        return $query->count();
    }

    /**
     * Return the total number of completed orders
     *
     * @return int
     */
    protected function getTotalCompletedOrderSum($range, $callback)
    {
        $query = Order::query();
        $query->whereIn('status_id', setting('completed_order_status') ?? []);

        $callback($range, $query);

        return $query->count();
    }

    /**
     * Return the total number of delivery orders
     *
     * @param string $range
     *
     * @return int
     */
    protected function getTotalDeliveryOrderSum($range, $callback)
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
     *
     * @return int
     */
    protected function getTotalCollectionOrderSum($range, $callback)
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
     *
     * @return int
     */
    protected function getTotalReservedTableSum($range, $callback)
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
     *
     * @return int
     */
    protected function getTotalReservedGuestSum($range, $callback)
    {
        $query = Reservation::query();
        $query->where('status_id', setting('confirmed_reservation_status'));

        $callback($range, $query);

        return $query->sum('guest_num') ?? 0;
    }

    /**
     * Return the total number of reservations
     *
     * @return int
     */
    protected function getTotalReservationSum($range, $callback)
    {
        $query = Reservation::query();
        $query->where('status_id', '!=', setting('canceled_reservation_status'));

        $callback($range, $query);

        return $query->count();
    }

    /**
     * Return the total number of completed reservations
     *
     * @return int
     */
    protected function getTotalCompletedReservationSum($range, $callback)
    {
        $query = Reservation::query();
        $query->where('status_id', setting('confirmed_reservation_status'));

        $callback($range, $query);

        return $query->count();
    }
}
