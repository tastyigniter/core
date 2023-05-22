<?php

namespace Igniter\Admin\Models;

use Carbon\Carbon;
use Igniter\Admin\Classes\PermissionManager;
use Igniter\Admin\Classes\UserState;
use Igniter\Admin\Models\Concerns\SendsInvite;
use Igniter\Admin\Traits\Locationable;
use Igniter\Flame\Auth\Models\User as AuthUserModel;
use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Traits\Purgeable;
use Igniter\System\Models\Concerns\Switchable;
use Igniter\System\Traits\SendsMailTemplate;

/**
 * Users Model Class
 */
class User extends AuthUserModel
{
    use HasFactory;
    use Purgeable;
    use SendsInvite;
    use SendsMailTemplate;
    use Locationable;
    use Switchable;

    const LOCATIONABLE_RELATION = 'locations';

    /**
     * @var string The database table name
     */
    protected $table = 'admin_users';

    protected $primaryKey = 'user_id';

    public $timestamps = true;

    protected $fillable = ['username', 'super_user'];

    protected $appends = ['full_name'];

    protected $hidden = ['password'];

    protected $casts = [
        'user_role_id' => 'integer',
        'sale_permission' => 'integer',
        'language_id' => 'integer',
        'super_user' => 'boolean',
        'is_activated' => 'boolean',
        'reset_time' => 'datetime',
        'invited_at' => 'datetime',
        'activated_at' => 'datetime',
        'last_login' => 'datetime',
    ];

    public $relation = [
        'hasMany' => [
            'assignable_logs' => [\Igniter\Admin\Models\AssignableLog::class, 'foreignKey' => 'assignee_id'],
        ],
        'belongsTo' => [
            'role' => [\Igniter\Admin\Models\UserRole::class, 'foreignKey' => 'user_role_id'],
            'language' => [\Igniter\System\Models\Language::class],
        ],
        'belongsToMany' => [
            'groups' => [\Igniter\Admin\Models\UserGroup::class, 'table' => 'admin_users_groups'],
        ],
        'morphToMany' => [
            'locations' => [\Igniter\Admin\Models\Location::class, 'name' => 'locationable'],
        ],
    ];

    protected $purgeable = ['password_confirm'];

    public function getStaffNameAttribute()
    {
        return $this->name;
    }

    public function getStaffEmailAttribute()
    {
        return $this->email;
    }

    public function getFullNameAttribute($value)
    {
        return $this->name;
    }

    public function getAvatarUrlAttribute()
    {
        return '//www.gravatar.com/avatar/'.md5(strtolower(trim($this->email))).'.png?d=mm';
    }

    public function getSalePermissionAttribute($value)
    {
        return $value ?: 1;
    }

    public static function getDropdownOptions()
    {
        return static::whereIsEnabled()->dropdown('name');
    }

    //
    // Scopes
    //

    public function scopeWhereNotSuperUser($query)
    {
        $query->where('super_user', '!=', 1)->orWhereNull('super_user');
    }

    public function scopeWhereIsSuperUser($query)
    {
        $query->where('super_user', 1);
    }

    //
    // Events
    //

    public function beforeLogin()
    {
        app('translator.localization')->setSessionLocale(
            optional($this->language)->code ?? app()->getLocale()
        );
    }

    public function afterLogin()
    {
        $this->last_login = Carbon::now();
        $this->save();
    }

    public function extendUserQuery($query)
    {
        $query
            ->with(['role', 'groups', 'locations'])
            ->isEnabled();
    }

    public function isSuperUser()
    {
        return $this->super_user == 1;
    }

    /**
     * Reset a user password,
     */
    public function resetPassword()
    {
        $this->reset_code = $resetCode = $this->generateResetCode();
        $this->reset_time = Carbon::now();
        $this->save();

        return $resetCode;
    }

    //
    // Permissions
    //

    public function hasAnyPermission($permissions)
    {
        return $this->hasPermission($permissions, false);
    }

    public function hasPermission($permissions, $checkAll = true)
    {
        // Bail out if the user is a super user
        if ($this->isSuperUser()) {
            return true;
        }

        $staffPermissions = $this->getPermissions();

        if (is_string($permissions) && str_contains($permissions, ',')) {
            $permissions = explode(',', $permissions);
        }

        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }

        if (resolve(PermissionManager::class)->checkPermission(
            $staffPermissions, $permissions, $checkAll)
        ) {
            return true;
        }

        return false;
    }

    public function getPermissions()
    {
        $role = $this->role;

        $permissions = [];
        if ($role && is_array($role->permissions)) {
            $permissions = $role->permissions;
        }

        return $permissions;
    }

    //
    // Location
    //

    public function hasLocationAccess($location)
    {
        return $this->locations->contains(function ($model) use ($location) {
            return $model->location_id === $location->location_id;
        });
    }

    public function mailGetRecipients($type)
    {
        return [
            [$this->email, $this->name],
        ];
    }

    public function mailGetData()
    {
        $model = $this->fresh();

        return array_merge($model->toArray(), [
            'staff' => $model,
            'staff_name' => $model->name,
            'staff_email' => $model->email,
            'username' => $model->username,
        ]);
    }

    //
    // Assignment
    //

    public function canAssignTo()
    {
        return !UserState::forUser($this->user)->isAway();
    }

    public function hasGlobalAssignableScope()
    {
        return $this->sale_permission === 1;
    }

    public function hasGroupAssignableScope()
    {
        return $this->sale_permission === 2;
    }

    public function hasRestrictedAssignableScope()
    {
        return $this->sale_permission === 3;
    }

    //
    // Helpers
    //

    protected function sendInviteGetTemplateCode(): string
    {
        return 'igniter.admin::_mail.invite';
    }

    /**
     * Return the dates of all staff
     * @return array
     */
    public function getUserDates()
    {
        return $this->pluckDates('created_at');
    }

    public function getLocale()
    {
        return optional($this->language)->code;
    }

    /**
     * Create a new or update existing user locations
     *
     * @param array $locations
     *
     * @return bool
     */
    public function addLocations($locations = [])
    {
        return $this->locations()->sync($locations);
    }

    /**
     * Create a new or update existing user groups
     *
     * @param array $groups
     *
     * @return bool
     */
    public function addGroups($groups = [])
    {
        return $this->groups()->sync($groups);
    }

    public function register(array $attributes, $activate = false)
    {
        $user = new static;
        $user->name = array_get($attributes, 'name');
        $user->email = array_get($attributes, 'email');
        $user->username = array_get($attributes, 'username');
        $user->password = array_get($attributes, 'password');
        $user->language_id = array_get($attributes, 'language_id');
        $user->user_role_id = array_get($attributes, 'user_role_id');
        $user->super_user = array_get($attributes, 'super_user', false);
        $user->status = array_get($attributes, 'status', true);
        $user->save();

        if ($activate) {
            $user->completeActivation($user->getActivationCode());
        }

        // Prevents subsequent saves to this model object
        $user->password = null;

        if (array_key_exists('groups', $attributes)) {
            $user->groups()->attach($attributes['groups']);
        }

        if (array_key_exists('locations', $attributes)) {
            $user->locations()->attach($attributes['locations']);
        }

        return $user->reload();
    }
}
