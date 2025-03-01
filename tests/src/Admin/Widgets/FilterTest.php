<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Widgets;

use Igniter\Admin\Classes\FilterScope;
use Igniter\Admin\Models\Status;
use Igniter\Admin\Widgets\Filter;
use Igniter\Admin\Widgets\SearchBox;
use Igniter\Flame\Exception\SystemException;
use Igniter\Local\Facades\Location as LocationFacade;
use Igniter\Local\Models\Location;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Fixtures\Controllers\TestController;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->widgetConfig = [
        'context' => 'test-context',
        'search' => [
            'prompt' => 'Search text',
            'mode' => 'all',
        ],
        'scopes' => [
            'status' => [
                'label' => 'Status array option',
                'type' => 'select',
                'mode' => 'radio',
                'options' => [
                    1 => 'Active',
                    2 => 'Inactive',
                ],
            ],
            'status-model' => [
                'label' => 'Status model option',
                'type' => 'select',
                'modelClass' => Status::class,
            ],
            'status-dropdown' => [
                'label' => 'Status callback option',
                'type' => 'select',
                'options' => Status::getDropdownOptionsForOrder(...),
            ],
            'status-string-option' => [
                'label' => 'Status string option',
                'type' => 'select',
                'modelClass' => Status::class,
                'options' => 'getDropdownOptionsForOrder',
            ],
        ],
    ];
    $this->filterWidget = new Filter($this->controller, $this->widgetConfig);
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('js/vendor.datetime.js', 'vendor-datetime-js');
    Assets::shouldReceive('addJs')->once()->with('widgets/daterangepicker.js', 'daterangepicker-js');
    Assets::shouldReceive('addCss')->once()->with('formwidgets/datepicker.css', 'datepicker-css');

    $this->filterWidget->assetPath = [];

    $this->filterWidget->loadAssets();
});

it('renders correctly', function() {
    expect($this->filterWidget->render())->toBeString();
});

it('prepares variables correctly', function() {
    $this->filterWidget->prepareVars();

    expect($this->filterWidget->vars)
        ->toBeArray()
        ->toHaveKey('filterAlias')
        ->toHaveKey('filterId')
        ->toHaveKey('onSubmitHandler')
        ->toHaveKey('onClearHandler')
        ->toHaveKey('cssClasses')
        ->toHaveKey('search')
        ->toHaveKey('scopes');
});

it('returns search widget', function() {
    $this->filterWidget->prepareVars();

    $result = $this->filterWidget->getSearchWidget();

    expect($result)->toBeInstanceOf(SearchBox::class);
});

it('renders scope element correctly', function() {
    $scope = new FilterScope('status', 'Test');
    $this->filterWidget->prepareVars();

    expect($this->filterWidget->renderScopeElement($scope))->toBeString();
});

it('submits correctly and dispatches filter.submit event', function() {
    request()->request->add(['filter' => [
        'status' => 'value',
    ]]);

    $this->filterWidget->bindEvent('filter.submit', fn($params): array => ['triggered']);

    $result = $this->filterWidget->onSubmit();

    expect($result[0])->toEqual('triggered')
        ->and($this->filterWidget->getScopeValue('status'))->toEqual('value');
});

it('submits correctly when scope type is selectlist', function($scopeType, $scopeValue) {
    request()->request->add(['filter' => [
        'status' => $scopeValue,
    ]]);

    $this->widgetConfig['scopes']['status']['type'] = $scopeType;
    $filterWidget = new Filter($this->controller, $this->widgetConfig);

    $filterWidget->onSubmit();

    expect($filterWidget->getScopeValue('status'))->toEqual($scopeValue);
})->with([
    ['select', 'value'],
    ['selectlist', ['value']],
    ['checkbox', '1'],
    ['switch', '1'],
    ['date', '2021-01-01'],
    ['daterange', ['2021-01-01', '2021-01-31']],
    ['daterange', null],
]);

it('clears correctly', function() {
    request()->request->add(['filter' => [
        'status' => 'value',
    ]]);

    $this->filterWidget->onSubmit();

    expect($this->filterWidget->getScopeValue('status'))->toEqual('value')
        ->and($this->filterWidget->onClear())->toBeNull();

    $this->filterWidget->bindEvent('filter.submit', fn($params): array => ['triggered']);

    $response = $this->filterWidget->onClear();

    expect($this->filterWidget->getScopeValue('status'))->toBeNull()
        ->and($response)->toEqual(['triggered']);
});

it('returns select options', function() {
    $result = $this->filterWidget->getSelectOptions('status');

    expect($result)->toBeArray()
        ->toHaveKey('available')
        ->toHaveKey('active');
});

it('throws exception when model is missing', function() {
    $this->widgetConfig['scopes']['status-missing-model'] = [
        'label' => 'Status missing model',
        'type' => 'select',
        'options' => 'getDropdownOptionsForOrder',
    ];

    $filterWidget = new Filter($this->controller, $this->widgetConfig);

    expect(fn() => $filterWidget->getSelectOptions('status-missing-model'))
        ->toThrow(SystemException::class, sprintf(lang('igniter::admin.list.filter_missing_scope_model'), 'status-missing-model'));
});

it('throws exception when model method is missing', function() {
    $this->widgetConfig['scopes']['status-missing-model-method'] = [
        'label' => 'Status missing model method',
        'type' => 'select',
        'modelClass' => Status::class,
        'options' => 'getDropdownOptionsForInvalid',
    ];

    $filterWidget = new Filter($this->controller, $this->widgetConfig);

    expect(fn() => $filterWidget->getSelectOptions('status-missing-model-method'))
        ->toThrow(SystemException::class, sprintf(lang('igniter::admin.list.filter_missing_definitions'),
            Status::class, 'getDropdownOptionsForInvalid', 'status-missing-model-method',
        ));
});

it('returns select options from model method', function() {
    $result = $this->filterWidget->getSelectOptions('status-string-option');

    expect($result)->toBeArray()
        ->toHaveKey('available')
        ->toHaveKey('active')
        ->and($result['available'])->not->toBeEmpty();
});

it('returns select options from model', function() {
    Status::factory()->count(2)->create();

    $result = $this->filterWidget->getSelectOptions('status-model');

    expect($result)->toBeArray()
        ->toHaveKey('available')
        ->toHaveKey('active')
        ->and(count($result['available']))->toBeGreaterThanOrEqual(2);
});

it('returns select options from callback', function() {
    $result = $this->filterWidget->getSelectOptions('status-dropdown');

    expect($result)->toBeArray()
        ->toHaveKey('available')
        ->toHaveKey('active');
});

it('filters scope definition based on permission, context and location aware', function() {
    LocationFacade::setModel(Location::factory()->create());
    $this->widgetConfig['scopes']['restricted'] = [
        'label' => 'Restricted scope',
        'type' => 'select',
        'permissions' => 'Admin.ManageOrders',
    ];
    $this->widgetConfig['scopes']['context'] = [
        'label' => 'Context scope',
        'type' => 'select',
        'context' => ['context'],
    ];
    $this->widgetConfig['scopes']['location'] = [
        'label' => 'Location aware scope',
        'type' => 'select',
        'locationAware' => true,
    ];
    $this->widgetConfig['context'] = 'test-context';
    $filterWidget = new Filter($this->controller, $this->widgetConfig);

    $filterWidget->prepareVars();

    expect($filterWidget->vars['scopes'])->not->toHaveKey('restricted');
});

it('returns scope name', function() {
    $scope = new FilterScope('test', 'Test');
    $this->filterWidget->prepareVars();

    expect($this->filterWidget->getScopeName('status'))->toEqual('filter[status]')
        ->and($this->filterWidget->getScopeNameFrom('status'))->toEqual('name')
        ->and($this->filterWidget->getScopeName($scope))->toEqual('filter[test]');
});

it('sets scope value', function() {
    $scope = new FilterScope('test', 'Test');
    $this->filterWidget->setScopeValue($scope, 'value');

    expect($this->filterWidget->getSession('scope-test'))->toEqual('value');
});

it('returns scope', function() {
    $this->filterWidget->prepareVars();

    $result = $this->filterWidget->getScope('status');

    expect($result)->toBeInstanceOf(FilterScope::class)
        ->and($result->scopeName)->toEqual('status');
});

it('throws exception when scope is missing', function() {
    expect(fn() => $this->filterWidget->getScope('invalid-scope'))
        ->toThrow(SystemException::class, sprintf(lang('igniter::admin.list.filter_missing_scope_definitions'), 'invalid-scope'));
});

it('returns context', function() {
    expect($this->filterWidget->getContext())->toEqual('test-context');
});

it('applies date scope to query', function(string $type, $value, $config, $expected) {
    $this->widgetConfig['scopes']['status-'.$type] = array_merge([
        'label' => 'Status',
        'type' => $type,
    ], $config);
    $filterWidget = new Filter($this->controller, $this->widgetConfig);
    $filterWidget->prepareVars();
    $filterWidget->setScopeValue('status-'.$type, $value);

    $query = Status::query();

    $filterWidget->applyScopeToQuery('status-'.$type, $query);

    expect($query->toSql())->toContain($expected);
})->with([
    'date type with conditions' => ['date', '2023-01-01', ['conditions' => 'status_for = :filtered'], 'where status_for = 2023-01-01'],
    'date type with model scope' => ['date', '2023-01-01', ['scope' => 'isForOrder'], 'where `status_for` = ?'],
    'daterange type with conditions' => ['daterange', ['2023-01-01', '2023-01-31'], [
        'conditions' => 'created_at >= CAST(:filtered_start AS DATE) AND created_at <= CAST(:filtered_end AS DATE)',
    ], 'where created_at >= CAST("2023-01-01" AS DATE) AND created_at <= CAST("2023-01-31" AS DATE)'],
    'daterange type with scope' => ['daterange', ['2023-01-01', '2023-01-31'], ['scope' => 'isForOrder'], 'where `status_for` = ?'],
    'select type with conditions' => ['select', 'value', ['conditions' => 'status_for = :filtered'], "where status_for = 'value'"],
    'select type with multiple conditions' => ['select', ['value'], ['conditions' => ['status_for = :filtered']], "where status_for = 'value'"],
    'select type with multiple values' => ['select', ['2023-01-01', '2023-01-31'], ['conditions' => 'status_for = :filtered'], "where status_for = '2023-01-01','2023-01-31'"],
    'select type with model scope' => ['select', 'value', ['scope' => 'isForOrder'], 'where `status_for` = ?'],
    'disabled select type' => ['select', 'value', ['disabled' => true], 'from `statuses`'],
]);
