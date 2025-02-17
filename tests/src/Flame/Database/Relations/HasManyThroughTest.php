<?php

declare(strict_types=1);

namespace Igniter\Tests\Flame\Database\Relations;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

it('checks if parent uses soft deletes', function() {
    $status = new class extends Model
    {
        use SoftDeletes;
    };
    $page = new class extends Model {};
    $relation = new HasManyThrough(
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
