<?php

namespace Igniter\Tests\Flame\Database\Relations;

it('checks if parent does not use soft deletes', function() {
    $status = new class extends \Igniter\Flame\Database\Model {};
    $page = new class extends \Igniter\Flame\Database\Model {};
    $relation = new \Igniter\Flame\Database\Relations\HasOneThrough(
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
