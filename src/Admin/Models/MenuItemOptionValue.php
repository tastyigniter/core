<?php

namespace Igniter\Admin\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Validation;

/**
 * MenuItemOptionValue Model Class
 */
class MenuItemOptionValue extends Model
{
    use Validation;

    protected static $optionValuesCollection;

    /**
     * @var string The database table name
     */
    protected $table = 'menu_item_option_values';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'menu_option_value_id';

    protected $fillable = ['menu_option_id', 'option_value_id', 'override_price', 'priority', 'is_default'];

    public $appends = ['name', 'price'];

    protected $casts = [
        'menu_option_value_id' => 'integer',
        'menu_option_id' => 'integer',
        'option_value_id' => 'integer',
        'override_price' => 'float',
        'priority' => 'integer',
        'is_default' => 'boolean',
    ];

    public $relation = [
        'belongsTo' => [
            'menu' => [\Igniter\Admin\Models\Menu::class],
            'option_value' => [\Igniter\Admin\Models\MenuOptionValue::class],
            'menu_option' => [\Igniter\Admin\Models\MenuItemOption::class],
        ],
    ];

    public $rules = [
        ['menu_option_id', 'igniter::admin.menu_options.label_option_value_id', 'required|integer'],
        ['option_value_id', 'igniter::admin.menu_options.label_option_value', 'required|integer'],
        ['override_price', 'igniter::admin.menu_options.label_option_price', 'nullable|numeric|min:0'],
    ];

    public $timestamps = true;

    public function getNameAttribute()
    {
        return $this->option_value->name ?? null;
    }

    public function getPriceAttribute()
    {
        if (!is_null($this->override_price) || strlen($this->override_price)) {
            return $this->override_price;
        }

        return $this->option_value->price ?? null;
    }

    public function isDefault()
    {
        return $this->is_default == 1;
    }

    /**
     * Subtract or add to menu option item stock quantity
     *
     * @param int $quantity
     * @param bool $subtract
     * @return bool TRUE on success, or FALSE on failure
     */
    public function updateStock($quantity = 0, $subtract = true)
    {
        traceLog('MenuItemOptionValue::updateStock() has been deprecated, use Stock::updateStock() instead.');
    }
}
