<?php

namespace Igniter\Main\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\System\Models\Concerns\Defaultable;

/**
 * CustomerGroup Model Class
 */
class CustomerGroup extends Model
{
    use HasFactory;
    use Defaultable;

    /**
     * @var string The database table name
     */
    protected $table = 'customer_groups';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'customer_group_id';

    protected $casts = [
        'approval' => 'boolean',
    ];

    public $relation = [
        'hasMany' => [
            'customers' => \Igniter\Main\Models\Customer::class,
        ],
    ];

    public $timestamps = true;

    public static function getDropdownOptions()
    {
        return static::dropdown('group_name');
    }

    //
    // Accessors & Mutators
    //

    public function getCustomerCountAttribute($value)
    {
        return $this->customers()->count();
    }

    //
    //
    //

    public function requiresApproval()
    {
        return $this->approval == 1;
    }

    public function defaultableName()
    {
        return $this->group_name;
    }
}
