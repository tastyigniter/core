<?php

namespace Igniter\Admin\Models;

use Igniter\Flame\Database\Casts\Serialize;

class OrderMenu extends \Igniter\Flame\Database\Model
{
    protected $table = 'order_menus';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'order_menu_id';

    public $guarded = [];

    protected $casts = [
        'order_id' => 'integer',
        'menu_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'float',
        'subtotal' => 'float',
        'option_values' => Serialize::class,
    ];

    public $relation = [
        'belongsTo' => [
            'order' => \Igniter\Admin\Models\Order::class,
            'menu' => \Igniter\Admin\Models\Menu::class,
        ],
        'hasMany' => [
            'menu_options' => \Igniter\Admin\Models\OrderMenuOptionValue::class,
        ],
    ];
}
