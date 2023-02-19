<?php

namespace Igniter\Admin\Models;

use Igniter\Admin\Traits\Stockable;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Sortable;

/**
 * MenuOptionValue Model Class
 */
class MenuOptionValue extends Model
{
    use Sortable;
    use Stockable;

    protected static $ingredientOptionsCache;

    /**
     * @var string The database table name
     */
    protected $table = 'menu_option_values';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'option_value_id';

    protected $fillable = ['option_id', 'value', 'price', 'ingredients', 'priority'];

    protected $casts = [
        'option_value_id' => 'integer',
        'option_id' => 'integer',
        'price' => 'float',
        'priority' => 'integer',
    ];

    public $relation = [
        'belongsTo' => [
            'option' => [\Igniter\Admin\Models\MenuOption::class],
        ],
        'morphToMany' => [
            'ingredients' => [\Igniter\Admin\Models\Ingredient::class, 'name' => 'ingredientable'],
        ],
    ];

    public $sortable = [
        'sortOrderColumn' => 'priority',
        'sortWhenCreating' => true,
    ];

    public static function getDropDownOptions()
    {
        return static::dropdown('value');
    }

    public function getAllergensOptions()
    {
        return $this->getIngredientsOptions();
    }

    public function getIngredientsOptions()
    {
        if (self::$ingredientOptionsCache)
            return self::$ingredientOptionsCache;

        return self::$ingredientOptionsCache = Ingredient::dropdown('name')->all();
    }

    public function getStockableName()
    {
        return $this->value;
    }

    public function getStockableLocations()
    {
        return $this->option->locations;
    }

    //
    // Events
    //

    protected function beforeDelete()
    {
        $this->allergens()->detach();
    }

    /**
     * Create new or update existing menu allergens
     *
     * @param array $allergenIds if empty all existing records will be deleted
     *
     * @return bool
     */
    public function addMenuAllergens(array $allergenIds = [])
    {
        if (!$this->exists)
            return false;

        $this->allergens()->sync($allergenIds);
    }
}
