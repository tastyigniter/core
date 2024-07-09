<?php

namespace Igniter\Admin\Traits;

use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use Igniter\Local\Traits\LocationAwareWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait HasChartDatasets
{
    use LocationAwareWidget;

    protected array $datasetOptions = [
        'label' => null,
        'data' => [],
        'fill' => true,
        'backgroundColor' => null,
        'borderColor' => null,
    ];

    protected ?array $datasetsConfig = null;

    protected static $registeredDatasets = [];

    public static function registerDatasets($callback)
    {
        static::$registeredDatasets[] = $callback;
    }

    public function loadAssets()
    {
        $this->addJs('js/vendor.datetime.js', 'vendor-datetime-js');
        $this->addJs('js/vendor.chart.js', 'vendor-chart-js');

        $this->addCss('dashboardwidgets/charts.css', 'charts-css');
        $this->addJs('dashboardwidgets/charts.js', 'charts-js');
    }

    public function addDataset(string $code, array $config = [])
    {
        $this->datasetsConfig[$code] = $config;

        return $this;
    }

    public function mergeDataset(string $code, string $key, mixed $value)
    {
        $this->datasetsConfig[$code][$key] = array_merge($this->datasetsConfig[$code][$key] ?? [], $value);

        return $this;
    }

    protected function makeDataset(array $config, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $config['label'] = lang(array_pull($config, 'label', ''));

        if ($color = array_pull($config, 'color')) {
            [$r, $g, $b] = sscanf($color, '#%02x%02x%02x');
        } else {
            [$r, $g, $b] = [random_int(0, 255), random_int(0, 255), random_int(0, 255)];
        }

        $config['data'] = $this->getDatasets($config, $start, $end);

        return array_merge($this->datasetOptions, [
            'backgroundColor' => sprintf('rgba(%s, %s, %s, 0.5)', $r, $g, $b),
            'borderColor' => sprintf('rgb(%s, %s, %s)', $r, $g, $b),
        ], array_except($config, ['model', 'column', 'priority', 'datasetFrom']));
    }

    protected function listSets()
    {
        if (!is_null($this->datasetsConfig)) {
            return $this->datasetsConfig;
        }

        $result = $this->getDefaultSets();

        foreach (static::$registeredDatasets as $callback) {
            foreach ($callback() as $code => $config) {
                $result[$code] = $config;
            }
        }

        $this->datasetsConfig = $result;

        $this->fireSystemEvent('admin.charts.extendDatasets');

        $this->datasetsConfig = collect($this->datasetsConfig)
            ->mapWithKeys(function($config, $code) {
                if (array_key_exists('sets', $config)) {
                    $config['sets'] = sort_array($config['sets']);
                }

                return [$code => $config];
            })
            ->all();

        return $this->datasetsConfig;
    }

    protected function getDataDefinition($key, $default = null)
    {
        return array_get($this->listSets(), $this->getActiveDataset().'.'.$key, $default);
    }

    protected function getDatasets(array $config, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $dataPoints = $this->queryDatasets($config, $start, $end);

        return collect($this->getDatePeriod($start, $end))->map(function($date) use ($dataPoints) {
            return ['x' => $x = $date->format('Y-m-d'), 'y' => $dataPoints->get($x) ?? 0];
        })->all();
    }

    protected function queryDatasets(array $config, \DateTimeInterface $start, \DateTimeInterface $end): Collection
    {
        $dateColumnName = $config['column'];

        $query = $config['model']::query()->select(
            DB::raw('DATE_FORMAT('.$dateColumnName.', "%Y-%m-%d") as x'),
            DB::raw('count(*) as y')
        )->whereBetween($dateColumnName, [$start, $end])->groupBy('x');

        $this->locationApplyScope($query);

        return $query->get()->pluck('y', 'x');
    }

    protected function getDatePeriod(\DateTimeInterface $start, \DateTimeInterface $end): DatePeriod
    {
        return new DatePeriod(
            Carbon::parse($start)->startOfDay(),
            new DateInterval('P1D'),
            Carbon::parse($end)->endOfDay()
        );
    }
}
