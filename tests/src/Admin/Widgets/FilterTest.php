<?php

namespace Igniter\Tests\Admin\Widgets;

use Igniter\Admin\Classes\FilterScope;
use Igniter\Admin\Widgets\Filter;
use Igniter\Admin\Widgets\SearchBox;
use Igniter\System\Facades\Assets;
use Igniter\Tests\Admin\Fixtures\Controllers\TestController;
use Illuminate\View\Factory;

beforeEach(function() {
    $this->controller = resolve(TestController::class);
    $this->filterWidget = new Filter($this->controller, [
        'context' => 'test-context',
        'search' => [
            'prompt' => 'Search text',
            'mode' => 'all',
        ],
        'scopes' => [
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'mode' => 'radio',
                'options' => [
                    1 => 'Active',
                    2 => 'Inactive',
                ],
            ],
        ],
    ]);
});

it('loads assets correctly', function() {
    Assets::shouldReceive('addJs')->once()->with('js/vendor.datetime.js', 'vendor-datetime-js');
    Assets::shouldReceive('addJs')->once()->with('widgets/daterangepicker.js', 'daterangepicker-js');
    Assets::shouldReceive('addCss')->once()->with('formwidgets/datepicker.css', 'datepicker-css');

    $this->filterWidget->assetPath = [];

    $this->filterWidget->loadAssets();
});

it('renders correctly', function() {
    app()->instance('view', $viewMock = $this->createMock(Factory::class));

    $viewMock->method('exists')->willReturnMap([
        [$this->stringContains('filter/filter'), true],
        [$this->stringContains('searchbox/searchbox'), true],
    ]);

    expect($this->filterWidget->render())->toBeString();
})->throws(\Exception::class);

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

it('gets search widget', function() {
    $this->filterWidget->prepareVars();

    $result = $this->filterWidget->getSearchWidget();

    expect($result)->toBeInstanceOf(SearchBox::class);
});

it('renders scope element correctly', function() {
    $scope = new FilterScope('status', 'Test');
    $this->filterWidget->prepareVars();

    expect($this->filterWidget->renderScopeElement($scope))->toBeString();
});

it('submits correctly', function() {
    request()->request->add(['filter' => [
        'status' => 'value',
    ]]);

    $this->filterWidget->bindEvent('filter.submit', function($params) {
        return 'triggered';
    });

    $result = $this->filterWidget->onSubmit();

    expect($result[0])->toEqual('triggered')
        ->and($this->filterWidget->getScopeValue('status'))->toEqual('value');
});

it('clears correctly', function() {
    request()->request->add(['filter' => [
        'status' => 'value',
    ]]);

    $this->filterWidget->onSubmit();

    expect($this->filterWidget->getScopeValue('status'))->toEqual('value');

    $this->filterWidget->onClear();

    expect($this->filterWidget->getScopeValue('status'))->toBeNull();
});

it('gets select options', function() {
    $result = $this->filterWidget->getSelectOptions('status');

    expect($result)->toBeArray()
        ->toHaveKey('available')
        ->toHaveKey('active');
});

it('gets scope name', function() {
    $scope = new FilterScope('test', 'Test');
    $result = $this->filterWidget->getScopeName($scope);

    expect($result)->toEqual('filter[test]');
});

it('sets scope value', function() {
    $scope = new FilterScope('test', 'Test');
    $this->filterWidget->setScopeValue($scope, 'value');

    expect($this->filterWidget->getSession('scope-test'))->toEqual('value');
});

it('gets scope', function() {
    $this->filterWidget->prepareVars();

    $result = $this->filterWidget->getScope('status');

    expect($result)->toBeInstanceOf(FilterScope::class)
        ->and($result->scopeName)->toEqual('status');
});

it('gets context', function() {
    expect($this->filterWidget->getContext())->toEqual('test-context');
});
