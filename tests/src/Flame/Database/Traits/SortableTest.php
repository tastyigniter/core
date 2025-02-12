<?php

namespace Igniter\Tests\Flame\Database\Traits;

use Igniter\System\Models\Country;

it('sets sort order when creating if not set', function() {
    expect(Country::query()->sorted()->toSql())->toContain('order by `priority` asc');
});

it('throws exception if itemIds and itemOrders count do not match', function() {
    $model = new Country;

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Invalid setSortableOrder call - count of itemIds do not match count of itemOrders');

    $model->setSortableOrder(1, [1, 2]);
});

it('sets sortable order correctly', function() {
    $model = new Country;
    $model1 = Country::factory()->create(['priority' => 0]);
    $model2 = Country::factory()->create(['priority' => 0]);

    $model->setSortableOrder([$model1->getKey(), $model2->getKey()]);

    expect($model1->fresh()->priority)->toBe($model1->getKey())
        ->and($model2->fresh()->priority)->toBe($model2->getKey());
});
