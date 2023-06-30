<?php

namespace Igniter\System\Models\Concerns;

use Igniter\Flame\Exception\ValidationException;
use Illuminate\Database\Eloquent\Builder;

trait Defaultable
{
    protected static array $defaultModels = [];

    public static function bootDefaultable()
    {
        static::extend(function (self $model) {
            $model->mergeCasts([$model->defaultableGetColumn() => 'boolean']);

            if ($model->getGuarded() || $model->getFillable()) {
                $model->mergeFillable([$model->defaultableGetColumn()]);
            }
        });

        static::saved(function (self $model) {
            if ($model->wasChanged($model->defaultableGetColumn()) && $model->isDefault()) {
                $model->makeDefault();
            }
        });
    }

    public static function updateDefault(mixed $id): bool
    {
        return ($model = static::firstWhere((new static)->defaultableKeyName(), $id))
            ? $model->makeDefault() : false;
    }

    public static function getDefaultKey(): mixed
    {
        return ($default = static::getDefault())
            ? $default->{$default->defaultableKeyName()} : null;
    }

    public static function getDefault(): ?static
    {
        if (array_key_exists(static::class, static::$defaultModels)) {
            return static::$defaultModels[static::class];
        }

        $query = (new static)->defaultable();
        if (in_array(Switchable::class, class_uses_recursive(static::class))) {
            $query->whereIsEnabled();
        }

        $defaultQuery = $query->applyDefaultable(true);
        if (!$defaultModel = $defaultQuery->first()) {
            if ($defaultModel = $query->first()) {
                $defaultModel->makeDefault();
            }
        }

        return static::$defaultModels[static::class] = $defaultModel;
    }

    public function makeDefault(): bool
    {
        if (in_array(Switchable::class, class_uses_recursive(static::class))) {
            if (!$this->{$this->switchableGetColumn()}) {
                throw new ValidationException([$this->switchableGetColumn() => sprintf(
                    lang('igniter::admin.alert_error_set_default'), $this->defaultableName()
                )]);
            }
        }

        static::withoutTimestamps(function () {
            $this->defaultable()
                ->where($this->defaultableGetColumn(), '!=', 0)
                ->update([$this->defaultableGetColumn() => 0]);

            $this->defaultable()
                ->where($this->defaultableKeyName(), $this->{$this->defaultableKeyName()})
                ->update([$this->defaultableGetColumn() => 1]);
        });

        return true;
    }

    public function isDefault(): bool
    {
        return (bool)$this->{$this->defaultableGetColumn()};
    }

    public function defaultableGetColumn(): string
    {
        if (defined(static::class.'::DEFAULTABLE_COLUMN')) {
            return static::DEFAULTABLE_COLUMN;
        }

        return 'is_default';
    }

    public function defaultableName(): string
    {
        return $this->name;
    }

    public function defaultableKeyName(): string
    {
        return $this->getKeyName();
    }

    public function defaultable(): Builder
    {
        return static::query();
    }

    public function scopeWhereIsDefault(Builder $query)
    {
        return $query->applyDefaultable(true);
    }

    public function scopeWhereNotDefault(Builder $query)
    {
        return $query->applyDefaultable(false);
    }

    public function scopeApplyDefaultable(Builder $query, bool $default = true)
    {
        return $default
            ? $query->where($this->qualifyColumn($this->defaultableGetColumn()), true)
            : $query->where($this->qualifyColumn($this->defaultableGetColumn()), '!=', true);
    }
}
