<?php

namespace Igniter\Flame\Database\Traits;

use Igniter\Flame\Database\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\Filters\FiltersScope;

trait HasQueryModifier
{
    protected array $queryModifierFilters = [];

    protected array $queryModifierSorts = [];

    protected string $queryModifierSortDirection = 'desc';

    protected array $queryModifierSearchableFields = [];

    public function scopeListFrontEnd(Builder $builder, array $options): LengthAwarePaginator
    {
        $this->applyFilters($options, $builder);

        $this->fireEvent('model.extendListFrontEndQuery', [$builder]);

        if (is_null($pageLimit = array_get($options, 'pageLimit'))) {
            return $builder;
        }

        return $builder->paginate($pageLimit, array_get($options, 'page', 1));
    }

    public function scopeApplyFilters(Builder $builder, array $options): Builder
    {
        $search = trim(array_get($options, 'search', ''));
        if (strlen($search) && $searchableFields = $this->queryModifierSearchableFields) {
            $builder->search($search, $searchableFields);
        }

        collect($this->queryModifierFilters)
            ->each(function ($value, $key) use ($builder, $options) {
                $params = (array)$value;
                if ($filterValue = array_get($options, $key, array_get($params, 'default'))) {
                    (new FiltersScope())($builder, $filterValue, $params[0]);
                }
            });

        $builder->applySorts((array)array_get($options, 'sort', []));

        return $builder;
    }

    public function scopeApplySorts(Builder $builder, array $sorts): Builder
    {
        foreach ($sorts as $sort) {
            if (in_array($sort, $this->queryModifierSorts)) {
                if (str_contains($sort, ' ')) {
                    [$sortField, $sortDirection] = explode(' ', $sort);
                    $builder->orderBy($sortField, $sortDirection);
                } else {
                    $builder->orderBy($sort, $this->queryModifierSortDirection());
                }
            }
        }

        return $builder;
    }

    public function queryModifierAddSorts(array $sorts): static
    {
        $this->queryModifierSorts = array_unique(array_merge($this->queryModifierSorts, $sorts));

        return $this;
    }

    protected function queryModifierAddFilters(array $filters): static
    {
        $this->queryModifierFilters = array_merge($this->queryModifierFilters, $filters);

        return $this;
    }

    protected function queryModifierAddSearchableFields(array $searchableFields): static
    {
        $this->queryModifierSearchableFields = array_unique(
            array_merge($this->queryModifierSearchableFields, $searchableFields)
        );

        return $this;
    }
}