<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\User\Models\AssignableLog;
use Igniter\User\Models\User;
use Igniter\User\Models\UserGroup;
use Illuminate\Pagination\LengthAwarePaginator;

it('syncs models and flushes cache', function() {
    $user = User::factory()->create();
    $userGroup = UserGroup::factory()->create();
    $builder = $user->groups();

    $builder->sync([$userGroup->getKey()]);

    expect($user->groups->count())->toBe(1)
        ->and($user->groups()->getOtherKey())->toBe('admin_users_groups.user_group_id');
});

it('attaches and detaches related model', function() {
    User::flushEventListeners();
    $user = User::factory()->create();
    $userGroup = UserGroup::factory()->create();

    $user->groups()->attach($userGroup->getKey());
    expect($user->fresh()->groups->count())->toBe(1);

    $user->groups()->detach($userGroup->getKey());
    expect($user->fresh()->groups->count())->toBe(0);
});

it('does not attach when event returns false', function() {
    $user = User::factory()->create();
    $userGroup = UserGroup::factory()->create();
    $builder = $user->groups();
    $user->bindEvent('model.relation.beforeAttach', fn(): false => false);

    $builder->attach($userGroup->getKey());

    expect($user->fresh()->groups->count())->toBe(0);
});

it('does not detach when event returns false', function() {
    $user = User::factory()->create();
    $builder = $user->groups();
    $user->bindEvent('model.relation.beforeDetach', fn(): false => false);

    $builder->detach();

    expect($user->fresh()->groups->count())->toBe(0);
});

it('associates and dissociates model correctly', function() {
    $user = User::factory()->create();
    $userGroup = UserGroup::factory()->create();
    $builder = $user->groups();
    $builder->add($userGroup);

    expect($user->fresh()->groups->count())->toBe(1);

    $builder->remove($userGroup);

    expect($user->fresh()->groups->count())->toBe(0);
});

it('associates a model when parent does not exists', function() {
    User::flushEventListeners();
    $user = User::factory()->make();
    $userGroup = UserGroup::factory()->create();
    $builder = $user->groups();
    $builder->add($userGroup);
    $user->save();

    expect($user->fresh()->groups->count())->toBe(1);
});

it('paginates query', function() {
    $user = new class extends User
    {
        public $relation = [
            'hasMany' => [
                'assignable_logs' => [AssignableLog::class, 'foreignKey' => 'assignee_id', 'count' => true],
            ],
            'belongsToMany' => [
                'groups' => [
                    UserGroup::class,
                    'table' => 'admin_users_groups',
                    'pivot' => 'admin_users_groups',
                    'pivotKey' => 'user_group_id',
                    'foreignKey' => 'user_id',
                    'count' => true,
                    'default' => [],
                ],
            ],
        ];

        public function __construct(array $attributes = [])
        {
            parent::__construct($attributes);

            $this->relation['hasMany']['assignable_logs']['scope'] = function($query) {
                return $query;
            };
        }
    };
    $user->save();

    $userGroup = UserGroup::factory()->create();
    $builder = $user->groups();
    $builder->add($userGroup);

    expect($builder->paginate(1))->toBeInstanceOf(LengthAwarePaginator::class);

    $assignableLog = AssignableLog::create();
    $builder = $user->assignable_logs();
    $builder->add($assignableLog);

    expect($builder->paginate(1))->toBeInstanceOf(LengthAwarePaginator::class);
});

it('sets simple value with null', function() {
    User::flushEventListeners();
    $user = User::factory()->create();
    $user->groups()->setSimpleValue(null);
    $user->save();

    expect($user->fresh()->groups->count())->toBe(0);

    $status = new class extends User
    {
        public $relation = ['belongsToMany' => ['page' => [UserGroup::class, 'timestamps' => true]]];
    };
    $status->save();
    $status->page()->setSimpleValue(null);

    expect($status->page_id)->toBeNull();

});

it('sets simple value with model instance', function() {
    User::flushEventListeners();
    $user = User::factory()->create();
    $userGroup = UserGroup::factory()->create();
    $user->groups()->setSimpleValue($userGroup);
    $user->save();

    expect($user->fresh()->groups->count())->toBe(1);
});

it('sets simple value with collection of models', function() {
    User::flushEventListeners();
    $user = User::factory()->create();
    $userGroups = UserGroup::factory()->count(2)->create();
    $user->groups()->setSimpleValue($userGroups);
    $user->save();

    expect($user->fresh()->groups->count())->toBe(2);
});

it('sets simple value with array of models', function() {
    User::flushEventListeners();
    $user = User::factory()->create();
    $userGroup1 = UserGroup::factory()->create();
    $userGroup2 = UserGroup::factory()->create();

    expect($user->groups()->getSimpleValue())->toBeArray();

    $user->groups()->setSimpleValue([$userGroup1, $userGroup2]);
    $user->save();

    expect($user->fresh()->groups->count())->toBe(2)
        ->and($user->groups()->getSimpleValue())->toBe([$userGroup1->getKey(), $userGroup2->getKey()]);
});
