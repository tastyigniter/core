<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Concerns;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\NestedSet\QueryBuilder;
use Igniter\Tests\Fixtures\Models\TestModel;
use Illuminate\Database\Query\Builder;

it('searches with all mode', function() {
    $model = new TestModel;
    $queryBuilder = new QueryBuilder($model->newQuery()->getQuery());
    $queryBuilder->setModel($model);

    $queryBuilder->search('term', ['column1', 'column2'], 'all');

    expect($queryBuilder->toRawSql())
        ->toContain("where ((lower(column1) like '%term%') or (lower(column2) like '%term%'))");
});

it('searches with all mode with multiple words', function() {
    $model = new TestModel;
    $queryBuilder = new QueryBuilder($model->newQuery()->getQuery());
    $queryBuilder->setModel($model);

    $queryBuilder->search('term1 term2', ['column1', 'column2'], 'all');

    expect($queryBuilder->toRawSql())
        ->toContain("where ((lower(column1) like '%term1%' and lower(column1) like '%term2%')")
        ->and($queryBuilder->toRawSql())
        ->toContain("or (lower(column2) like '%term1%' and lower(column2) like '%term2%'))");
});

it('searches with any mode with multiple words', function() {
    $model = new TestModel;
    $queryBuilder = new QueryBuilder($model->newQuery()->getQuery());
    $queryBuilder->setModel($model);

    $queryBuilder->search('term1 term2', ['column1'], 'any');

    expect($queryBuilder->toRawSql())
        ->toContain("where ((lower(column1) like '%term1%' or lower(column1) like '%term2%'))");
});

it('searches with exact mode with multiple words', function() {
    $model = new TestModel;
    $queryBuilder = new QueryBuilder($model->newQuery()->getQuery());
    $queryBuilder->setModel($model);

    $queryBuilder->search('term1 term2', ['column1'], 'exact');

    expect($queryBuilder->toRawSql())
        ->toContain("where (lower(column1) like '%term1 term2%')");
});

it('or searches with all mode', function() {
    $model = new TestModel;
    $queryBuilder = new QueryBuilder($model->newQuery()->getQuery());
    $queryBuilder->setModel($model);

    $queryBuilder->orSearch('term', 'column1', 'all');

    expect($queryBuilder->toRawSql())->toContain("where ((lower(column1) like '%term%'))");
});

it('applies like clause', function() {
    $model = new TestModel;
    $queryBuilder = new QueryBuilder($model->newQuery()->getQuery());
    $queryBuilder->setModel($model);
    $queryBuilder->like('column1', 'value1');

    $queryBuilder->orLike('column2', 'value2');

    expect($queryBuilder->toRawSql())->toContain("where lower(column1) like '%value1%'")
        ->and($queryBuilder->toRawSql())->toContain("or lower(column2) like '%value2%'");
});

it('applies like clause with none side', function() {
    $model = new TestModel;
    $queryBuilder = new QueryBuilder($model->newQuery()->getQuery());
    $queryBuilder->setModel($model);

    $queryBuilder->like('column', 'value', 'none');

    expect($queryBuilder->toRawSql())->toContain("where lower(column) like 'value'");
});

it('applies like clause with before side', function() {
    $model = new TestModel;
    $queryBuilder = new QueryBuilder($model->newQuery()->getQuery());
    $queryBuilder->setModel($model);

    $queryBuilder->like('column', 'value', 'before');

    expect($queryBuilder->toRawSql())->toContain("where lower(column) like '%value'");
});

it('applies like clause with after side', function() {
    $model = new TestModel;
    $queryBuilder = new QueryBuilder($model->newQuery()->getQuery());
    $queryBuilder->setModel($model);

    $queryBuilder->like('column', 'value', 'after');

    expect($queryBuilder->toRawSql())->toContain("where lower(column) like 'value%'");
});

it('paginates results', function() {
    $queryBuilder = mock(QueryBuilder::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $queryBuilder->shouldReceive('toBase->getCountForPagination')->andReturn(100);
    $queryBuilder->shouldReceive('forPage->get')->andReturn(collect(['result1', 'result2']));
    $queryBuilder->setQuery($builder = mock(Builder::class));
    $builder->shouldReceive('from')->with('table');
    $model = mock(Model::class);
    $model->shouldReceive('getTable')->andReturn('table');
    $queryBuilder->setModel($model);
    $model->shouldReceive('getPerPage')->andReturn(15);

    $paginator = $queryBuilder->paginate(15, ['*']);

    expect($paginator->total())->toBe(100)
        ->and($paginator->items())->toBe(['result1', 'result2']);
});

it('paginates returns empty results', function() {
    $queryBuilder = mock(QueryBuilder::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $queryBuilder->shouldReceive('toBase->getCountForPagination')->andReturn(0);
    $queryBuilder->shouldReceive('forPage->get')->andReturn(collect());
    $queryBuilder->setQuery($builder = mock(Builder::class));
    $builder->shouldReceive('from')->with('table');
    $model = mock(Model::class);
    $model->shouldReceive('getTable')->andReturn('table');
    $queryBuilder->setModel($model);
    $model->shouldReceive('newCollection')->andReturn(collect());
    $model->shouldReceive('getPerPage')->andReturn(15);

    $paginator = $queryBuilder->paginate(15, ['*']);

    expect($paginator->total())->toBe(0)
        ->and($paginator->items())->toBeEmpty();
});

it('simple paginates results', function() {
    $queryBuilder = mock(QueryBuilder::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $queryBuilder->shouldReceive('get')->andReturn(collect(['result1', 'result2']));
    $queryBuilder->setQuery($builder = mock(Builder::class));
    $builder->shouldReceive('from')->with('table');
    $builder->shouldReceive('skip')->andReturnSelf();
    $builder->shouldReceive('take')->andReturnSelf();
    $model = mock(Model::class);
    $model->shouldReceive('getTable')->andReturn('table');
    $queryBuilder->setModel($model);
    $model->shouldReceive('getPerPage')->andReturn(15);
    $model->shouldReceive('hasNamedScope')->andReturnFalse();

    $paginator = $queryBuilder->simplePaginate(15, ['*']);

    expect($paginator->items())->toBe(['result1', 'result2']);
});
