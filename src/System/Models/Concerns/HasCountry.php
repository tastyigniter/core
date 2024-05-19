<?php

namespace Igniter\System\Models\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait HasCountry
{
    public static function bootHasCountry()
    {
        static::saving(function(self $model) {
            if ($model->countryIsSingleRelationType()) {
                $model->country()->associate($model);
            } else {
                $model->country()->sync($model);
            }
        });
    }

    public function scopeWhereCountry(Builder $query, $countryId): Builder
    {
        $qualifiedColumnName = $this->getCountryRelationObject()->getQualifiedForeignKeyName();

        if ($this->countryIsSingleRelationType()) {
            return $query->where($qualifiedColumnName, $countryId);
        }

        return $query->whereHas($this->getCountryRelationName(), function(Builder $query) use ($qualifiedColumnName, $countryId) {
            return $query->where($qualifiedColumnName, $countryId);
        });
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
        $relationName = $this->locationableRelationName();

        return $this->{$relationName}();
    }

    protected function countryIsSingleRelationType(): bool
    {
        $relationType = $this->getRelationType($this->getCountryRelationName());

        return in_array($relationType, ['hasOne', 'belongsTo', 'morphOne']);
    }
}