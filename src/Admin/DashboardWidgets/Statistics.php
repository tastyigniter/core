<?php

namespace Igniter\Admin\DashboardWidgets;

use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Cart\Models\Order;
use Igniter\Flame\Exception\SystemException;
use Igniter\Local\Traits\LocationAwareWidget;
use Igniter\Reservation\Models\Reservation;
use Igniter\System\Models\Settings;
use Igniter\User\Models\Customer;

/**
 * Statistic dashboard widget.
 */
class Statistics extends BaseDashboardWidget
{
    use LocationAwareWidget;

    /** A unique alias to identify this widget. */
    protected string $defaultAlias = 'statistics';

    protected ?array $cardDefinition = null;

    protected static array $registeredCards = [];

    public static function registerCards(\Closure $callback)
    {
        static::$registeredCards[] = $callback;
    }

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
            'card' => [
                'label' => 'igniter::admin.dashboard.text_stats_card',
                'default' => 'sale',
                'type' => 'select',
                'placeholder' => 'igniter::admin.text_please_select',
                'options' => $this->getCardOptions(),
                'validationRule' => 'required|alpha_dash',
            ],
        ];
    }

    public function getActiveCard()
    {
        return $this->property('card', 'sale');
    }

    public function loadAssets()
    {
        $this->addCss('statistics.css', 'statistics-css');
    }

    protected function getCardOptions()
    {
        return array_map(function ($context) {
            return array_get($context, 'label');
        }, $this->listCards());
    }

    protected function prepareVars()
    {
        $this->vars['statsContext'] = $context = $this->getActiveCard();
        $this->vars['statsLabel'] = $this->getCardDefinition('label', '--');
        $this->vars['statsColor'] = $this->getCardDefinition('color', 'success');
        $this->vars['statsIcon'] = $this->getCardDefinition('icon', 'fa fa-bar-chart-o');
        $this->vars['statsCount'] = $this->getValue($context);
    }

    protected function listCards()
    {
        $result = $this->getDefaultCards();

        foreach (static::$registeredCards as $callback) {
            foreach ($callback() as $code => $config) {
                $result[$code] = $config;
            }
        }

        return $result;
    }

    protected function getDefaultCards(): array
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

    protected function getCardDefinition($key, $default = null)
    {
        if (is_null($this->cardDefinition)) {
            $this->cardDefinition = array_get($this->listCards(), $this->getActiveCard());
        }

        return array_get($this->cardDefinition, $key, $default);
    }

    protected function getValue(string $cardCode): string
    {
        $start = $this->property('startDate', now()->subMonth());
        $end = $this->property('endDate', now());

        if ($dataFromCallable = $this->getCardDefinition('valueFrom')) {
            return $dataFromCallable($cardCode, $start, $end);
        }

        return $this->getValueForDefaultCard($cardCode, $start, $end);
    }

    protected function getValueForDefaultCard(string $cardCode, $start, $end)
    {
        $contextMethod = 'getTotal'.studly_case($cardCode).'Sum';

        throw_unless($this->methodExists($contextMethod), new SystemException(sprintf(
            'The card [%s] does must have a defined method in [%s]',
            $cardCode, get_class($this).'::'.$contextMethod
        )));

        $count = $this->$contextMethod(function ($query) use ($start, $end) {
            $this->locationApplyScope($query);
            $query->whereBetween('created_at', [$start, $end]);
        });

        return empty($count) ? 0 : $count;
    }

    /**
     * Return the total amount of order sales
     */
    protected function getTotalSaleSum(callable $callback): string
    {
        $query = Order::query();
        $query->where('status_id', '>', '0')
            ->where('status_id', '!=', Settings::get('canceled_order_status'));

        $callback($query);

        return currency_format($query->sum('order_total') ?? 0);
    }

    /**
     * Return the total amount of lost order sales
     */
    protected function getTotalLostSaleSum(callable $callback): string
    {
        $query = Order::query();
        $query->where(function ($query) {
            $query->where('status_id', '<=', '0');
            $query->orWhere('status_id', Settings::get('canceled_order_status'));
        });

        $callback($query);

        return currency_format($query->sum('order_total') ?? 0);
    }

    /**
     * Return the total amount of cash payment order sales
     */
    protected function getTotalCashPaymentSum(callable $callback): string
    {
        $query = Order::query();
        $query->where(function ($query) {
            $query->where('status_id', '>', '0');
            $query->where('status_id', '!=', Settings::get('canceled_order_status'));
        })->where('payment', 'cod');

        $callback($query);

        return currency_format($query->sum('order_total') ?? 0);
    }

    /**
     * Return the total number of customers
     */
    protected function getTotalCustomerSum(callable $callback): int
    {
        $query = Customer::query();

        $callback($query);

        return $query->count();
    }

    /**
     * Return the total number of orders placed
     */
    protected function getTotalOrderSum(callable $callback): int
    {
        $query = Order::query();

        $callback($query);

        return $query->count();
    }

    /**
     * Return the total number of completed orders
     */
    protected function getTotalCompletedOrderSum(callable $callback): int
    {
        $query = Order::query();
        $query->whereIn('status_id', Settings::get('completed_order_status') ?? []);

        $callback($query);

        return $query->count();
    }

    /**
     * Return the total number of delivery orders
     */
    protected function getTotalDeliveryOrderSum(callable $callback): string
    {
        $query = Order::query();
        $query->where(function ($query) {
            $query->where('order_type', '1');
            $query->orWhere('order_type', 'delivery');
        });

        $callback($query);

        return currency_format($query->sum('order_total') ?? 0);
    }

    /**
     * Return the total number of collection orders
     */
    protected function getTotalCollectionOrderSum(callable $callback): string
    {
        $query = Order::query();
        $query->where(function ($query) {
            $query->where('order_type', '2');
            $query->orWhere('order_type', 'collection');
        });

        $callback($query);

        return currency_format($query->sum('order_total') ?? 0);
    }

    /**
     * Return the total number of reserved tables
     */
    protected function getTotalReservedTableSum(callable $callback): int
    {
        $query = Reservation::with('tables');
        $query->where('status_id', Settings::get('confirmed_reservation_status'));

        $callback($query);

        $result = $query->get();

        $result->pluck('tables')->flatten();

        return $result->count();
    }

    /**
     * Return the total number of reserved table guests
     */
    protected function getTotalReservedGuestSum(callable $callback): int
    {
        $query = Reservation::query();
        $query->where('status_id', Settings::get('confirmed_reservation_status'));

        $callback($query);

        return $query->sum('guest_num') ?? 0;
    }

    /**
     * Return the total number of reservations
     */
    protected function getTotalReservationSum(callable $callback): int
    {
        $query = Reservation::query();
        $query->where('status_id', '!=', Settings::get('canceled_reservation_status'));

        $callback($query);

        return $query->count();
    }

    /**
     * Return the total number of completed reservations
     */
    protected function getTotalCompletedReservationSum(callable $callback): int
    {
        $query = Reservation::query();
        $query->where('status_id', Settings::get('confirmed_reservation_status'));

        $callback($query);

        return $query->count();
    }
}
