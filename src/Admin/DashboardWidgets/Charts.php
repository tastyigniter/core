<?php

namespace Igniter\Admin\DashboardWidgets;

use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Admin\Traits\HasChartDatasets;
use Igniter\Cart\Models\Order;
use Igniter\Reservation\Models\Reservation;
use Igniter\User\Models\Customer;

/**
 * Charts dashboard widget.
 */
class Charts extends BaseDashboardWidget
{
    use HasChartDatasets;

    /**
     * @var string A unique alias to identify this widget.
     */
    protected string $defaultAlias = 'charts';

    protected array $datasetOptions = [
        'label' => null,
        'data' => [],
        'fill' => true,
        'backgroundColor' => null,
        'borderColor' => null,
    ];

    public array $contextDefinitions = [];

    public function initialize()
    {
        $this->setProperty('rangeFormat', 'MMMM D, YYYY');
    }

    public function defineProperties(): array
    {
        return [
            'title' => [
                'label' => 'igniter::admin.dashboard.label_widget_title',
                'default' => 'igniter::admin.dashboard.text_reports_chart',
            ],
        ];
    }

    /**
     * Renders the widget.
     */
    public function render()
    {
        return $this->makePartial('charts/charts');
    }

    public function listContext(): array
    {
        $this->contextDefinitions = [
            'customer' => [
                'label' => 'lang:igniter::admin.dashboard.charts.text_customers',
                'color' => '#4DB6AC',
                'model' => Customer::class,
                'column' => 'created_at',
            ],
            'order' => [
                'label' => 'lang:igniter::admin.dashboard.charts.text_orders',
                'color' => '#64B5F6',
                'model' => Order::class,
                'column' => 'order_date',
            ],
            'reservation' => [
                'label' => 'lang:igniter::admin.dashboard.charts.text_reservations',
                'color' => '#BA68C8',
                'model' => Reservation::class,
                'column' => 'reserve_date',
            ],
        ];

        $this->fireSystemEvent('admin.charts.extendDatasets');

        return $this->contextDefinitions;
    }

    protected function getDatasets(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $result = [];
        foreach ($this->listContext() as $context => $config) {
            $result[] = $this->makeDataset($config, $start, $end);
        }

        return $result;
    }
}
