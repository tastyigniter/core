<?php

namespace Igniter\Flame\Database\NestedSet;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Kalnoy\Nestedset\QueryBuilder as QueryBuilderBase;

class QueryBuilder extends QueryBuilderBase
{
    /**
     * Perform a search on this query for term found in columns.
     *
     * @param string $term Search query
     * @param array $columns Table columns to search
     * @param string $mode Search mode: all, any, exact.
     *
     * @return self
     */
    public function search($term, $columns = [], $mode = 'all')
    {
        return $this->searchInternal($term, $columns, $mode, 'and');
    }

    /**
     * Add an "or search where" clause to the query.
     *
     * @param string $term Search query
     * @param array $columns Table columns to search
     * @param string $mode Search mode: all, any, exact.
     *
     * @return self
     */
    public function orSearch($term, $columns = [], $mode = 'all')
    {
        return $this->searchInternal($term, $columns, $mode, 'or');
    }

    /**
     * Convenient method for where like clause
     *
     * @param string $column
     * @param string $side
     * @param string $boolean
     *
     * @return \Igniter\Flame\Database\Builder
     */
    public function like($column, $value, $side = 'both', $boolean = 'and')
    {
        return $this->likeInternal($column, $value, $side, $boolean);
    }

    /**
     * Convenient method for or where like clause
     *
     * @param string $column
     * @param string $side
     *
     * @return self
     */
    public function orLike($column, $value, $side = 'both')
    {
        return $this->likeInternal($column, $value, $side, 'or');
    }

    /**
     * Internal method to apply a search constraint to the query.
     * Mode can be any of these options:
     * - all: result must contain all words
     * - any: result can contain any word
     * - exact: result must contain the exact phrase
     *
     * @param array $columns
     *
     * @return $this
     */
    protected function searchInternal($term, $columns, $mode, $boolean)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        if (!$mode) {
            $mode = 'all';
        }

        if ($mode === 'exact') {
            $this->where(function($query) use ($columns, $term) {
                foreach ($columns as $field) {
                    if (!strlen($term)) {
                        continue;
                    }
                    $query->orLike($field, $term, 'both');
                }
            }, null, null, $boolean);
        } else {
            $words = explode(' ', $term);
            $wordBoolean = $mode === 'any' ? 'or' : 'and';

            $this->where(function($query) use ($columns, $words, $wordBoolean) {
                foreach ($columns as $field) {
                    $query->orWhere(function($query) use ($field, $words, $wordBoolean) {
                        foreach ($words as $word) {
                            if (!strlen($word)) {
                                continue;
                            }
                            $query->like($field, $word, 'both', $wordBoolean);
                        }
                    });
                }
            }, null, null, $boolean);
        }

        return $this;
    }

    protected function likeInternal($column, $value, $side = null, $boolean = 'and')
    {
        $column = $this->toBase()->raw(sprintf('lower(%s)', $column));
        $value = mb_strtolower(trim($value));

        if ($side === 'none') {
            $value = $value;
        } elseif ($side === 'before') {
            $value = "%{$value}";
        } elseif ($side === 'after') {
            $value = "{$value}%";
        } else {
            $value = "%{$value}%";
        }

        return $this->where($column, 'like', $value, $boolean);
    }

    /**
     * Paginate the given query.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public function paginate($perPage = null, $page = null, $columns = ['*'], $pageName = 'page', $total = null)
    {
        if (is_array($page)) {
            $columns = $page;
            $page = null;
        }

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        $results = ($total = $this->toBase()->getCountForPagination())
            ? $this->forPage($page, $perPage)->get($columns)
            : $this->model->newCollection();

        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = null, $page = null, $columns = ['*'], $pageName = 'page')
    {
        if (is_array($page)) {
            $columns = $page;
            $page = null;
        }

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        // Next we will set the limit and offset for this query so that when we get the
        // results we get the proper section of results. Then, we'll create the full
        // paginator instances for these results with the given page and per page.
        $this->skip(($page - 1) * $perPage)->take($perPage + 1);

        return new Paginator($this->get($columns), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }
}
