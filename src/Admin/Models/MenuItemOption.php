<?php

namespace Igniter\Admin\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Purgeable;
use Igniter\Flame\Database\Traits\Validation;

class MenuItemOption extends Model
{
    use Purgeable;
    use Validation;

    /**
     * @var string The database table name
     */
    protected $table = 'menu_item_options';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'menu_option_id';

    protected $fillable = ['option_id', 'menu_id', 'is_required', 'priority', 'min_selected', 'max_selected'];

    protected $casts = [
        'menu_option_id' => 'integer',
        'option_id' => 'integer',
        'menu_id' => 'integer',
        'is_required' => 'boolean',
        'priority' => 'integer',
        'min_selected' => 'integer',
        'max_selected' => 'integer',
    ];

    public $relation = [
        'hasMany' => [
            'menu_option_values' => [
                \Igniter\Admin\Models\MenuItemOptionValue::class,
                'foreignKey' => 'menu_option_id',
                'delete' => true,
            ],
        ],
        'belongsTo' => [
            'menu' => [\Igniter\Admin\Models\Menu::class],
            'option' => [\Igniter\Admin\Models\MenuOption::class],
        ],
    ];

    public $appends = ['option_name', 'display_type'];

    public $rules = [
        ['menu_id', 'igniter::admin.menus.label_option', 'required|integer'],
        ['option_id', 'igniter::admin.menus.label_option_id', 'required|integer'],
        ['priority', 'igniter::admin.menu_options.label_option', 'integer'],
        ['is_required', 'igniter::admin.menu_options.label_option_required', 'boolean'],
        ['min_selected', 'igniter::admin.menu_options.label_min_selected', 'integer|lte:max_selected'],
        ['max_selected', 'igniter::admin.menu_options.label_max_selected', 'integer|gte:min_selected'],
    ];

    protected $purgeable = ['menu_option_values'];

    public $with = ['option'];

    public $timestamps = true;

    public function getOptionNameAttribute($value = null)
    {
        return $value ?: optional($this->option)->option_name;
    }

    public function getDisplayTypeAttribute()
    {
        return optional($this->option)->display_type;
    }

    public function getOptionValuesAttribute()
    {
        return $this->option->option_values->map(function ($optionValue) {
            $menuOptionValue = $this->menu_option_values->firstWhere('option_value_id', $optionValue->getKey());

            $optionValue->menu_option_value_id = $menuOptionValue?->menu_option_value_id;
            $optionValue->menu_option_id = $menuOptionValue?->menu_option_id;
            $optionValue->price = $menuOptionValue->price ?? $optionValue->price;
            $optionValue->override_price = $menuOptionValue?->override_price;
            $optionValue->is_default = $menuOptionValue?->is_default;
            $optionValue->is_enabled = !is_null($menuOptionValue);

            return $optionValue;
        });
    }

    //
    // Helpers
    //
    public function isRequired()
    {
        return $this->is_required;
    }

    public function isSelectDisplayType()
    {
        return $this->display_type === 'select';
    }

    /**
     * Create new or update existing menu option values
     *
     * @param array $optionValues if empty all existing records will be deleted
     *
     * @return bool
     */
    public function addMenuOptionValues(array $optionValues = [])
    {
        $menuOptionId = $this->getKey();
        if (!is_numeric($menuOptionId)) {
            return false;
        }

        $idsToKeep = [];
        foreach ($optionValues as $value) {
            $menuOptionValue = $this->menu_option_values()->firstOrNew([
                'menu_option_value_id' => array_get($value, 'menu_option_value_id'),
                'menu_option_id' => $menuOptionId,
            ])->fill(array_except($value, ['menu_option_value_id', 'menu_option_id']));
            $menuOptionValue->saveOrFail();
            $idsToKeep[] = $menuOptionValue->getKey();
        }

        $this->menu_option_values()
            ->where('menu_option_id', $menuOptionId)
            ->whereNotIn('menu_option_value_id', $idsToKeep)
            ->delete();

        return count($idsToKeep);
    }
}
