<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Models;

use Igniter\Admin\Models\Status;

class TestStatusModel extends Status
{
    public $relation = [
        'belongsTo' => [
            'status' => [TestStatusModel::class, 'status_id'],
        ],
    ];
}
