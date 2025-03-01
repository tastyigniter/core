<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\DashboardWidgets;

use Igniter\Admin\DashboardWidgets\Charts;
use Igniter\Admin\Http\Controllers\Dashboard;
use Igniter\System\Facades\Assets;
use Igniter\System\Models\MailTemplate;
use Illuminate\Support\Facades\Event;

beforeEach(function() {
    $this->controller = resolve(Dashboard::class);
    $this->charts = new Charts($this->controller, [
        'startDate' => now()->subDays(30),
        'endDate' => now(),
    ]);
});

it('initializes with default properties', function() {
    expect($this->charts->contextDefinitions)->toBe([])
        ->and($this->charts->property('rangeFormat'))->toBe('MMMM D, YYYY')
        ->and($this->charts->property('dataset'))->toBe('reports');
});

it('defines properties correctly', function() {
    $properties = $this->charts->defineProperties();

    expect($properties)->toHaveKey('dataset')
        ->and($properties['dataset'])
        ->toHaveKey('label', 'admin::lang.dashboard.text_charts_dataset')
        ->toHaveKey('default', 'reports')
        ->toHaveKey('type', 'select')
        ->toHaveKey('placeholder', 'lang:admin::lang.text_please_select')
        ->toHaveKey('options', $this->charts->getDatasetOptions(...))
        ->toHaveKey('validationRule', 'required|alpha_dash');
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('js/vendor.datetime.js', 'vendor-datetime-js');
    Assets::shouldReceive('addJs')->once()->with('js/vendor.chart.js', 'vendor-chart-js');
    Assets::shouldReceive('addCss')->once()->withArgs(fn($css, $alias): bool => ends_with($css, 'dashboardwidgets/charts.css') && $alias === 'charts-css');
    Assets::shouldReceive('addJs')->once()->withArgs(fn($js, $alias): bool => ends_with($js, 'dashboardwidgets/charts.js') && $alias === 'charts-js');

    $this->charts->loadAssets();
});

it('renders widget correctly', function() {
    $result = $this->charts->render();

    expect($result)->toBeString();
});

it('tests prepareVars', function() {
    Charts::registerDatasets(fn(): array => [
        'newDataset' => [
            'label' => 'igniter::admin.dashboard.text_reports_chart',
            'sets' => [
                'orders' => [
                    'model' => MailTemplate::class,
                    'column' => 'created_at',
                    'priority' => 1,
                    'datasetFrom' => fn(): array => [],
                ],
            ],
        ],
    ]);

    $this->charts->render();

    expect($this->charts->vars['chartContext'])->toBe('reports')
        ->and($this->charts->vars['chartType'])->toBe('line')
        ->and($this->charts->vars['chartLabel'])->toBe('igniter::admin.dashboard.text_reports_chart')
        ->and($this->charts->vars['chartIcon'])->toBe('fa fa-bar-chart-o')
        ->and($this->charts->vars['chartData'])->toBeArray()->toHaveKey('datasets');
});

it('returns active dataset', function() {
    $dataset = $this->charts->getActiveDataset();

    expect($dataset)->toBe('reports');
});

it('loads dataset from', function() {
    $this->travelTo(now()->setMonth(1));

    Charts::registerDatasets(fn(): array => [
        'newDataset' => [
            'label' => 'igniter::admin.dashboard.text_reports_chart',
            'datasetFrom' => fn(): array => [
                'datasets' => [
                    [
                        'data' => [
                            ['x' => '2021-01-01', 'y' => 10],
                            ['x' => '2021-01-02', 'y' => 20],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $this->charts->setProperty('dataset', 'newDataset');
    $data = $this->charts->getData();

    expect($data)->toHaveKey('datasets')
        ->and($data['datasets'][0]['data'])->toHaveCount(2);
});

it('adds dataset correctly', function() {
    $this->travelTo(now()->setMonth(1));

    $this->charts->addDataset('reports', [
        'label' => 'New Dataset',
        'sets' => [
            'newDataset' => [
                'model' => MailTemplate::class,
                'column' => 'created_at',
                'priority' => 1,
                'datasetFrom' => fn(): array => [],
            ],
        ],
    ]);

    $data = $this->charts->getData();
    expect($data)->toHaveKey('datasets')
        ->and($data['datasets'][0]['data'])->toHaveCount(31);
});

it('merges dataset correctly', function() {
    $this->charts->addDataset('reports', [
        'label' => 'New Dataset',
        'sets' => [
            'newDataset' => [
                'model' => MailTemplate::class,
                'column' => 'created_at',
                'priority' => 1,
                'datasetFrom' => fn(): array => [],
            ],
        ],
    ]);
    $this->charts->mergeDataset('reports', 'sets', [
        'newDataset' => [
            'model' => MailTemplate::class,
            'column' => 'updated_at',
            'priority' => 99,
            'extraConfig' => 'extra',
        ],
    ]);

    expect($this->charts->getData()['datasets'][0]['extraConfig'])->toBe('extra');
});

it('tests getDatasetOptions', function() {
    Event::fake();

    $options = $this->charts->getDatasetOptions();

    expect($options)->toBeArray()->toHaveKey('reports');

    Event::assertDispatched('admin.charts.extendDatasets');
});
