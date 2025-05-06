<?php

declare(strict_types=1);

namespace Igniter\Flame\Database\Connections;

use Igniter\Flame\Database\Query\Builder as QueryBuilder;

class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    /**
     * Get a new query builder instance.
     */
    public function query(): QueryBuilder
    {
        return new QueryBuilder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }
}
