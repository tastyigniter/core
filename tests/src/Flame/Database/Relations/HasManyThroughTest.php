<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Relations\HasManyThrough;
use Igniter\System\Models\Page;
use Illuminate\Database\Eloquent\SoftDeletes;

it('checks if parent uses soft deletes', function() {
    $status = new class extends Model
    {
        use SoftDeletes;
    };
    $page = new class extends Model
    {
    };
    $relation = new HasManyThrough(
        $status->newQuery(),
        $status,
        $page,
        'status_id',
        'page_id',
        'status_id',
        'page_id',
    );

    expect($relation->parentSoftDeletes())->toBeFalse()
        ->and($relation->getQualifiedRelatedKeyName())->toBeString();
});

it('returns simple value from loaded relation', function() {
    $status = new class extends Model
    {
        use SoftDeletes;

        protected $primaryKey = 'status_id';
    };
    $page = new class extends Page
    {
    };
    $relation = new HasManyThrough(
        $status->newQuery(),
        $status,
        $page,
        'status_id',
        'page_id',
        'status_id',
        'page_id',
        'status_pages',
    );

    $relation->addDefinedConstraints();
    $relation->getQuery()->getModel()->setRelation('status_pages', collect([
        (object)['status_id' => 1],
        (object)['status_id' => 2],
    ]));

    expect($relation->getSimpleValue())->toBe([1, 2]);
});
