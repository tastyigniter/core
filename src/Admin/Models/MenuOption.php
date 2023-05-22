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

    protected $fillable = ['option_id', 'option_name', 'display_type'];

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
            'locations' => [\Igniter\Admin\Models\Location::class, 'name' => 'locationable'],
        ],
    ];

    protected $purgeable = ['values'];

    public $timestamps = true;

    public static function getRecordEditorOptions()
    {
        $query = self::selectRaw('option_id, concat(option_name, " (", display_type, ")") AS display_name');

        if (!is_null($ids = AdminLocation::getIdOrAll())) {
            $query->whereHasLocation($ids);
        }

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
    // Helpers
    //

    public function isRequired()
    {
        return $this->required;
    }

    public function isSelectDisplayType()
    {
        return $this->display_type === 'select';
    }

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
            if (!array_key_exists('ingredients', $value)) {
                $value['ingredients'] = [];
            }

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

    public function attachRecordTo($menu)
    {
        $this->attachToMenu($menu);
    }

    public function attachToMenu($menu)
    {
        $menuItemOption = $menu->menu_options()->create([
            'option_id' => $this->getKey(),
        ]);

        $this->option_values()->get()->each(function ($optionValue) use ($menuItemOption) {
            $menuItemOption->menu_option_values()->create([
                'option_value_id' => $optionValue->option_value_id,
                'priority' => $optionValue->priority,
            ]);
        });
    }
}
