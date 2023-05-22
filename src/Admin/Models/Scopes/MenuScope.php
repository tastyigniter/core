<?php

namespace Igniter\Admin\Models\Scopes;

use Igniter\Flame\Database\Scope;
use Illuminate\Database\Eloquent\Builder;

class MenuScope extends Scope
{
    public function addApplyLocation()
    {
        return function (Builder $builder, $locationId) {
            return $builder
                ->whereHasOrDoesntHaveLocation($locationId)
                ->with(['categories' => function ($q) use ($locationId) {
                    $q->whereHasOrDoesntHaveLocation($locationId);
                    $q->isEnabled();
                }]);
        };
    }

    public function addApplyCategoryGroup()
    {
        return function (Builder $builder, $group) {
            return $builder->whereHas('categories', function (Builder $q) use ($group) {
                $q->groupBy($group);
            });
        };
    }

    public function addApplyOrderType()
    {
        return function (Builder $builder, $orderType) {
            return $builder->where(function (Builder $query) use ($orderType) {
                $query->whereNull('order_restriction')
                    ->orWhere('order_restriction', 'like', '%"'.$orderType.'"%');
            });
        };
    }

    public function addWhereHasAllergen()
    {
        return function (Builder $builder, $allergenId) {
            return $builder->whereHas('allergens', function (builder $q) use ($allergenId) {
                $q->where('allergen_id', $allergenId);
                $q->where('is_allergen', 1);
            });
        };
    }

    public function addWhereHasCategory()
    {
        return function (Builder $builder, $categoryId) {
            return $builder->whereHas('categories', function (builder $q) use ($categoryId) {
                if (is_numeric($categoryId)) {
                    $q->where('categories.category_id', $categoryId);
                } else {
                    $q->whereSlug($categoryId);
                }
            });
        };
    }

    public function addWhereHasIngredient()
    {
        return function (Builder $builder, $ingredientId) {
            return $builder->whereHas('ingredients', function (builder $q) use ($ingredientId) {
                $q->where('ingredient_id', $ingredientId);
            });
        };
    }

    public function addWhereHasMealtime()
    {
        return function (Builder $builder, $mealtimeId) {
            return $builder->whereHas('mealtimes', function (builder $q) use ($mealtimeId) {
                $q->where('mealtimes.mealtime_id', $mealtimeId);
            });
        };
    }
}
