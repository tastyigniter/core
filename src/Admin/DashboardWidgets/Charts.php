<?php

declare(strict_types=1);

namespace Igniter\Admin\DashboardWidgets;

use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Admin\Traits\HasChartDatasets;
use Igniter\Local\Traits\LocationAwareWidget;

/**
 * Charts dashboard widget.
 */
class Charts extends BaseDashboardWidget
{
    use HasChartDatasets;
    use LocationAwareWidget;

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

    public string $rangeFormat = 'MMMM D, YYYY';

    public array $dataset = [];

    protected ?array $datasetsConfig = null;

    protected static $registeredDatasets = [];

    public function initialize()
    {
        $this->setProperty('rangeFormat', 'MMMM D, YYYY');
    }

    public function defineProperties(): array
    {
        return [
            'dataset' => [
                'label' => 'admin::lang.dashboard.text_charts_dataset',
                'default' => 'reports',
                'type' => 'select',
                'placeholder' => 'lang:admin::lang.text_please_select',
                'options' => [$this, 'getDatasetOptions'],
                'validationRule' => 'required|alpha_dash',
            ],
        ];
    }

    public function loadAssets()
    {
        $this->addJs('js/vendor.datetime.js', 'vendor-datetime-js');
        $this->addJs('js/vendor.chart.js', 'vendor-chart-js');

        $this->addCss('dashboardwidgets/charts.css', 'charts-css');
        $this->addJs('dashboardwidgets/charts.js', 'charts-js');
    }

    /**
     * Renders the widget.
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('charts/charts');
    }

    protected function prepareVars()
    {
        $this->vars['chartContext'] = $this->getActiveDataset();
        $this->vars['chartType'] = $this->getDataDefinition('type', 'line');
        $this->vars['chartLabel'] = $this->getDataDefinition('label', '--');
        $this->vars['chartIcon'] = $this->getDataDefinition('icon', 'fa fa-bar-chart-o');
        $this->vars['chartData'] = $this->getData();
    }

    public function getActiveDataset()
    {
        return $this->property('dataset', 'reports');
    }

    public function getData()
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        if ($datasetFromCallable = $this->getDataDefinition('datasetFrom')) {
            return $datasetFromCallable($this->getActiveDataset(), $start, $end);
        }

        $datasets = [];
        $definitions = $this->getDataDefinition('sets') ?? [];
        foreach (array_filter($definitions) as $config) {
            $datasets[] = $this->makeDataset($config, $start, $end);
        }

        return ['datasets' => $datasets];
    }

    public function getDatasetOptions()
    {
        return array_map(function($context) {
            return array_get($context, 'label');
        }, $this->listSets());
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

    public static function registerDatasets($callback)
    {
        static::$registeredDatasets[] = $callback;
    }

    public static function clearRegisteredDatasets()
    {
        static::$registeredDatasets = [];
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

    protected function getDefaultSets()
    {
        return [
            'reports' => [
                'label' => 'igniter::admin.dashboard.text_reports_chart',
                'sets' => [],
            ],
        ];
    }
}
