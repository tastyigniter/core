<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Igniter\Flame\Assetic\Filter;

use Igniter\Flame\Assetic\Asset\AssetInterface;

/**
 * A collection of filters.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class FilterCollection implements FilterInterface, \IteratorAggregate, \Countable
{
    private $filters = [];

    public function __construct($filters = [])
    {
        foreach ($filters as $filter) {
            $this->ensure($filter);
        }
    }

    /**
     * Checks that the current collection contains the supplied filter.
     *
     * If the supplied filter is another filter collection, each of its
     * filters will be checked.
     * @param \Igniter\Flame\Assetic\Filter\FilterInterface $filter
     */
    public function ensure(FilterInterface $filter)
    {
        if ($filter instanceof \Traversable) {
            foreach ($filter as $f) {
                $this->ensure($f);
            }
        }
        elseif (!in_array($filter, $this->filters, true)) {
            $this->filters[] = $filter;
        }
    }

    public function all()
    {
        return $this->filters;
    }

    public function clear()
    {
        $this->filters = [];
    }

    public function filterLoad(AssetInterface $asset)
    {
        foreach ($this->filters as $filter) {
            $filter->filterLoad($asset);
        }
    }

    public function filterDump(AssetInterface $asset)
    {
        foreach ($this->filters as $filter) {
            $filter->filterDump($asset);
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->filters);
    }

    public function count()
    {
        return count($this->filters);
    }
}
