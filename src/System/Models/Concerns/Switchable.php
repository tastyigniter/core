<?php

namespace Igniter\System\Models\Concerns;

trait Switchable
{
    public function switchableGetColumn(): string
    {
        if (defined(static::class.'::SWITCHABLE_COLUMN')) {
            return static::SWITCHABLE_COLUMN;
        }

        return 'status';
    }

    public function isEnabled(): bool
    {
        return (bool)$this->{$this->switchableGetColumn()};
    }

    public function isDisabled(): bool
    {
        return (bool)$this->{$this->switchableGetColumn()};
    }

    public function scopeIsEnabled($query)
    {
        return $query->whereIsEnabled();
    }

    public function scopeWhereIsEnabled($query)
    {
        return $query
            ->whereNotNull($this->qualifyColumn($this->switchableGetColumn()))
            ->where($this->qualifyColumn($this->switchableGetColumn()), true);
    }

    public function scopeWhereIsDisabled($query)
    {
        return $query->where($this->qualifyColumn($this->switchableGetColumn()), '!=', true);
    }

    public function scopeApplySwitchable($query, $switch = true)
    {
        return $query->where($this->qualifyColumn($this->switchableGetColumn()), $switch);
    }
}