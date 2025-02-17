<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Relations\HasOneThrough;

it('checks if parent does not use soft deletes', function() {
    $status = new class extends Model {};
    $page = new class extends Model {};
    $relation = new HasOneThrough(
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
