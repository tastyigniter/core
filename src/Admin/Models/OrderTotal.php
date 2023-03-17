<?php

namespace Igniter\Admin\Models;

class OrderTotal extends \Igniter\Flame\Database\Model
{
    protected $table = 'order_totals';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'order_total_id';

    public $guarded = [];

    protected $casts = [
        'order_id' => 'integer',
        'value' => 'float',
        'priority' => 'integer',
        'is_summable' => 'boolean',
    ];

    public $relation = [
        'belongsTo' => [
            'order' => \Igniter\Admin\Models\Order::class,
        ],
    ];
}
