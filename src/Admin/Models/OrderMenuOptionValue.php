<?php

namespace Igniter\Admin\Models;

class OrderMenuOptionValue extends \Igniter\Flame\Database\Model
{
    protected $table = 'order_menu_options';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'order_option_id';

    public $guarded = [];

    public $appends = ['order_option_category'];

    protected $casts = [
        'order_menu_id' => 'integer',
        'menu_option_id' => 'integer',
        'menu_option_value_id' => 'integer',
        'quantity' => 'integer',
        'order_option_price' => 'float',
    ];

    public $relation = [
        'belongsTo' => [
            'order' => \Igniter\Admin\Models\Order::class,
            'order_menu' => \Igniter\Admin\Models\OrderMenu::class,
            'menu_option' => \Igniter\Admin\Models\MenuItemOption::class,
            'menu_option_value' => \Igniter\Admin\Models\MenuItemOptionValue::class,
        ],
    ];

    public function getOrderOptionCategoryAttribute()
    {
        return $this->menu_option->option_name;
    }
}
