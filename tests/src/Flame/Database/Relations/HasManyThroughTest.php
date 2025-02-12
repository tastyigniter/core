<?php

namespace Igniter\Tests\Flame\Database\Relations;

it('checks if parent uses soft deletes', function() {
    $status = new class extends \Igniter\Flame\Database\Model
    {
        use \Illuminate\Database\Eloquent\SoftDeletes;
    };
    $page = new class extends \Igniter\Flame\Database\Model
    {
    };
    $relation = new \Igniter\Flame\Database\Relations\HasManyThrough(
        $status->newQuery(),
        $status,
        $page,
        'status_id',
        'page_id',
        'status_id',
        'page_id',
    );

    expect($relation->parentSoftDeletes())->toBeFalse();
});
