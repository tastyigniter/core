<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\Local\Models\Location;
use Igniter\User\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

it('adds where constraints', function() {
    $user = User::factory()->create();
    $builder = $user->locations();

    expect($builder->toSql())->toContain('where `locationables`.`locationable_id` = ? and `locationables`.`locationable_type` = ?')
        ->and($builder->newPivotQuery()->toSql())->toContain('where `locationables`.`locationable_id` = ? and `locationable_type` = ?')
        ->and($builder->newPivot())->toBeInstanceOf(MorphPivot::class);
});

it('adds where constraints to eager loading', function() {
    $user = User::factory()->create();
    $user = User::with('locations')->whereKey($user->getKey())->first();

    expect($user->locations)->not()->toBeNull();
});

it('get relation existence query', function() {
    $user = User::factory()->create();
    $builder = $user->locations();

    expect($builder->getRelationExistenceQuery($builder->getQuery(), $user->query())->toSql())
        ->toContain('where `locationables`.`locationable_id` = ? and `locationables`.`locationable_type` = ?');
});

it('attaches and detaches related model', function() {
    User::flushEventListeners();
    $user = User::factory()->create();
    $location = Location::factory()->create();

    $user->locations()->attach($location->getKey());
    expect($user->fresh()->locations->count())->toBe(1);

    $user->locations()->detach($location->getKey());
    expect($user->fresh()->locations->count())->toBe(0);
});
