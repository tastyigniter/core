<?php

namespace Igniter\Admin\Models;

use Igniter\Admin\Facades\AdminLocation;
use Igniter\Admin\Traits\Locationable;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Purgeable;

/**
 * MenuOption Model Class
 */
class MenuOption extends Model
{
    use Locationable;
    use Purgeable;

    const LOCATIONABLE_RELATION = 'locations';

    /**
     * @var string The database table name
     */
    protected $table = 'menu_options';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'option_id';

    protected $fillable = ['option_id', 'option_name', 'display_type', 'update_related_menu_item'];

    protected $casts = [
        'option_id' => 'integer',
        'priority' => 'integer',
    ];

    public $relation = [
        'hasMany' => [
            'menu_options' => [\Igniter\Admin\Models\MenuItemOption::class, 'foreignKey' => 'option_id', 'delete' => true],
            'option_values' => [\Igniter\Admin\Models\MenuOptionValue::class, 'foreignKey' => 'option_id', 'delete' => true],
        ],
        'hasManyThrough' => [
            'menu_option_values' => [
                \Igniter\Admin\Models\MenuItemOptionValue::class,
                'through' => \Igniter\Admin\Models\MenuItemOption::class,
                'throughKey' => 'menu_option_id',
                'foreignKey' => 'option_id',
            ],
        ],
        'morphToMany' => [
            'allergens' => [\Igniter\Admin\Models\Ingredient::class, 'name' => 'allergenable'],
            'locations' => [\Igniter\Admin\Models\Location::class, 'name' => 'locationable'],
        ],
    ];

    protected $purgeable = ['values'];

    public $timestamps = true;

    public static function getRecordEditorOptions()
    {
        $query = self::selectRaw('option_id, concat(option_name, " (", display_type, ")") AS display_name');

        if (!is_null($ids = AdminLocation::getIdOrAll()))
            $query->whereHasLocation($ids);

        return $query->orderBy('option_name')->dropdown('display_name');
    }

    public static function getDisplayTypeOptions()
    {
        return [
            'radio' => 'lang:igniter::admin.menu_options.text_radio',
            'checkbox' => 'lang:igniter::admin.menu_options.text_checkbox',
            'select' => 'lang:igniter::admin.menu_options.text_select',
            'quantity' => 'lang:igniter::admin.menu_options.text_quantity',
        ];
    }

    //
    // Events
    //

    protected function afterSave()
    {
        $this->restorePurgedValues();

        if (array_key_exists('values', $this->attributes))
            $this->addOptionValues($this->attributes['values']);

        if ($this->update_related_menu_item)
            $this->updateRelatedMenuItemsOptionValues();
    }

    protected function beforeDelete()
    {
        $this->allergens()->detach();
        $this->locations()->detach();
    }

    //
    // Helpers
    //

    /**
     * Return all option values by option_id
     *
     * @param int $option_id
     *
     * @return array
     */
    public static function getOptionValues($option_id = null)
    {
        $query = self::orderBy('priority')->from('option_values');

        if ($option_id !== false) {
            $query->where('option_id', $option_id);
        }

        return $query->get();
    }

    /**
     * Create a new or update existing option values
     *
     * @param array $optionValues
     *
     * @return bool
     */
    public function addOptionValues($optionValues = [])
    {
        $optionId = $this->getKey();

        $idsToKeep = [];
        foreach ($optionValues as $value) {
            if (!array_key_exists('allergens', $value))
                $value['allergens'] = [];

            $optionValue = $this->option_values()->firstOrNew([
                'option_value_id' => array_get($value, 'option_value_id'),
                'option_id' => $optionId,
            ])->fill(array_except($value, ['option_value_id', 'option_id']));

            $optionValue->saveOrFail();
            $idsToKeep[] = $optionValue->getKey();
        }

        $this->option_values()->where('option_id', $optionId)
            ->whereNotIn('option_value_id', $idsToKeep)->delete();

        $this->menu_option_values()
            ->whereNotIn('option_value_id', $idsToKeep)->delete();

        return count($idsToKeep);
    }

    public function attachToMenu($menu)
    {
        $menuItemOption = $menu->menu_options()->create([
            'option_id' => $this->getKey(),
        ]);

        $this->option_values()->get()->each(function ($model) use ($menuItemOption) {
            $menuItemOption->menu_option_values()->create([
                'menu_option_id' => $menuItemOption->menu_option_id,
                'option_value_id' => $model->option_value_id,
                'new_price' => $model->price,
            ]);
        });
    }

    /**
     * Overwrite any menu items this option is attached to
     *
     * @return void
     */
    protected function updateRelatedMenuItemsOptionValues()
    {
        $optionValues = $this->option_values()->get()->map(function ($optionValue) {
            return [
                'menu_option_id' => $this->option_id,
                'option_value_id' => $optionValue->option_value_id,
                'new_price' => $optionValue->price,
                'quantity' => 0,
                'priority' => $optionValue->priority,
            ];
        })->all();

        $this->menu_options->each(function ($menuOption) use ($optionValues) {
            $menuOption->addMenuOptionValues($optionValues);
        });
    }
}
