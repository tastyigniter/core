<?php

namespace Igniter\System\Models\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait HasCountry
{
    public function scopeWhereCountry(Builder $query, $countryId): Builder
    {
        $qualifiedColumnName = $this->getCountryRelationObject()->getQualifiedForeignKeyName();

        return $query->where($qualifiedColumnName, $countryId);
    }

    protected function getCountryRelationName(): string
    {
        if (defined(static::class.'::COUNTRY_RELATION')) {
            return static::COUNTRY_RELATION;
        }

        return 'country';
    }

    protected function getCountryRelationObject(): Relation
    {
        $relationName = $this->getCountryRelationName();

        return $this->{$relationName}();
    }
}
