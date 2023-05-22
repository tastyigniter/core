<?php

namespace Igniter\Main\Models\Concerns;

use Igniter\Flame\Database\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait HasCustomer
{
    public function scopeApplyCustomer(Builder $query, $customerId): Builder
    {
        $qualifiedColumnName = $this->getCustomerRelationObject()->getQualifiedForeignKeyName();

        if ($this->customerIsSingleRelationType()) {
            return $query->where($qualifiedColumnName, $customerId);
        }

        return $query->whereHas($this->getCustomerRelationName(), function (Builder $query) use ($qualifiedColumnName, $customerId) {
            return $query->where($qualifiedColumnName, $customerId);
        });
    }

    protected function getCustomerRelationName(): string
    {
        if (defined(static::class.'::CUSTOMER_RELATION')) {
            return static::CUSTOMER_RELATION;
        }

        return 'customer';
    }

    protected function getCustomerRelationObject(): Relation
    {
        $relationName = $this->locationableRelationName();

        return $this->{$relationName}();
    }

    protected function customerIsSingleRelationType(): bool
    {
        $relationType = $this->getRelationType($this->getCountryRelationName());

        return in_array($relationType, ['hasOne', 'belongsTo', 'morphOne']);
    }
}