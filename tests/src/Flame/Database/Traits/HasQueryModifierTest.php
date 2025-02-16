<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Traits;

use Igniter\Flame\Database\Builder;
use Igniter\System\Models\Country;
use Illuminate\Pagination\LengthAwarePaginator;

function setupModelForApplyFilters(): Country
{
    Country::factory()->times(3)->create(['country_name' => 'SearchTestCountry', 'status' => 1]);
    Country::factory()->times(3)->create(['country_name' => 'TestCountry', 'status' => 1]);

    return new class extends Country
    {
        protected array $queryModifierFilters = [
            'name' => 'applyName',
        ];

        protected array $queryModifierSearchableFields = ['country_name'];

        protected array $queryModifierSorts = [
            'country_name asc', 'country_name desc',
        ];

        public function scopeApplyName($query, $value)
        {
            return $query->orWhere('country_name', $value);
        }
    };
}

it('applies filters and returns builder when pageLimit is not set', function() {
    $model = setupModelForApplyFilters();
    $model->queryModifierAddFilters(['status' => 'applySwitchable']);

    $paginatedList = $model->query()->listFrontEnd([
        'name' => 'TestCountry',
        'status' => 1,
    ]);

    expect($paginatedList)->toBeInstanceOf(Builder::class)
        ->and($paginatedList->count())->toBe(3)
        ->and($model->queryModifierGetFilters())->toHaveKey('status', 'applySwitchable');
});

it('applies filters and returns paginated result when pageLimit is set', function() {
    $model = setupModelForApplyFilters();

    $paginatedList = $model->query()->listFrontEnd([
        'pageLimit' => 2,
        'search' => 'SearchTestCountry',
        'name' => 'TestCountry',
    ]);

    expect($paginatedList)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($paginatedList->total())->toBe(6)
        ->and($paginatedList->perPage())->toBe(2)
        ->and($paginatedList->currentPage())->toBe(1)
        ->and($paginatedList->items())->toHaveCount(2);
});

it('applies search filter', function() {
    $model = setupModelForApplyFilters();
    $model->queryModifierAddSearchableFields(['iso_code_2']);

    $paginatedList = $model->query()->listFrontEnd([
        'pageLimit' => 10,
        'search' => 'SearchTestCountry',
    ]);

    expect($paginatedList->total())->toBe(3)
        ->and($model->queryModifierGetSearchableFields())->toContain('iso_code_2');
});

it('applies sorts', function() {
    $model = setupModelForApplyFilters();

    $paginatedList = $model->query()->listFrontEnd([
        'pageLimit' => 10,
        'name' => 'TestCountry',
        'sort' => 'country_name desc',
    ]);

    expect($paginatedList->total())->toBe(3)
        ->and($paginatedList->first()->country_name)->toBe('TestCountry');
});

it('applies multiple sorts', function() {
    $model = setupModelForApplyFilters();
    $model->queryModifierAddSorts(['iso_code_2 asc', 'iso_code_2 desc']);

    $paginatedList = $model->listFrontEnd([
        'pageLimit' => 10,
        'name' => 'TestCountry',
        'sort' => ['country_name desc', 'iso_code_2'],
    ]);

    expect($paginatedList->total())->toBe(3)
        ->and($paginatedList->first()->country_name)->toBe('TestCountry')
        ->and($model->queryModifierGetSorts())->toContain('iso_code_2 asc', 'iso_code_2 desc');
});

it('adds sorts to the list', function() {
    $model = setupModelForApplyFilters();

    $model->queryModifierAddSorts(['country_name asc', 'country_name desc']);

    $paginatedList = $model->query()->listFrontEnd([
        'pageLimit' => 10,
        'name' => 'TestCountry',
        'sort' => 'country_name desc',
    ]);

    expect($paginatedList->total())->toBe(3)
        ->and($paginatedList->first()->country_name)->toBe('TestCountry');
});
