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

    public function loadAssets()
    {
        $this->addJs('js/vendor.datetime.js', 'vendor-datetime-js');
        $this->addJs('js/vendor.chart.js', 'vendor-chart-js');

        $this->addCss('dashboardwidgets/charts.css', 'charts-css');
        $this->addJs('dashboardwidgets/charts.js', 'charts-js');
    }

    public function onFetchDatasets(): array
    {
        $start = post('start');
        $end = post('end');

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        if ($start->eq($end)) {
            $start = $start->startOfDay();
            $end = $end->endOfDay();
        }

        return $this->getDatasets($start, $end);
    }

    protected function getDatasets(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return [
            $this->makeDataset([], $start, $end),
        ];
    }

    protected function makeDataset(array $config, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        [$r, $g, $b] = sscanf($config['color'], '#%02x%02x%02x');
        $backgroundColor = sprintf('rgba(%s, %s, %s, 0.5)', $r, $g, $b);
        $borderColor = sprintf('rgb(%s, %s, %s)', $r, $g, $b);

        return array_merge($this->datasetOptions, [
            'label' => lang($config['label']),
            'data' => $this->queryDatasets($config, $start, $end),
            'backgroundColor' => $backgroundColor,
            'borderColor' => $borderColor,
        ]);
    }

    protected function queryDatasets(array $config, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $modelClass = $config['model'];
        $dateColumnName = $config['column'];

        $dateColumn = DB::raw('DATE_FORMAT('.$dateColumnName.', "%Y-%m-%d") as x');
        $query = $modelClass::select($dateColumn, DB::raw('count(*) as y'));
        $query->whereBetween($dateColumnName, [$start, $end])->groupBy('x');

        $dateRanges = $this->getDatePeriod($start, $end);
        $this->locationApplyScope($query);

        return $this->getPointsArray($dateRanges, $query->get());
    }

    protected function getDatePeriod(\DateTimeInterface $start, \DateTimeInterface $end): DatePeriod
    {
        return new DatePeriod(
            Carbon::parse($start)->startOfDay(),
            new DateInterval('P1D'),
            Carbon::parse($end)->endOfDay()
        );
    }

    protected function getPointsArray(DatePeriod $dateRanges, Collection $result): array
    {
        $points = [];
        $keyedResult = $result->pluck('y', 'x');
        foreach ($dateRanges as $date) {
            $x = $date->format('Y-m-d');
            $points[] = [
                'x' => $x,
                'y' => $keyedResult->get($x) ?? 0,
            ];
        }

        return $points;
    }
}
