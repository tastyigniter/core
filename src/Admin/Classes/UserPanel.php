<?php

namespace Igniter\Admin\Classes;

use Igniter\Admin\Events\Navigation\ExtendUserMenuLinks;
use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Facades\AdminLocation;

/**
 * Admin User Panel
 */
class UserPanel
{
    protected $user;

    protected $location;

    protected static $menuLinksCache = [];

    public static function forUser($user = null, $location = null)
    {
        $instance = new static;
        $instance->user = $user ?: AdminAuth::getUser();
        $instance->location = $location ?: AdminLocation::current();

        return $instance;
    }

    public static function listMenuLinks($menu, $item, $user)
    {
        if (self::$menuLinksCache)
            return self::$menuLinksCache;

        $items = collect([
            'userState' => [
                'priority' => 10,
                'label' => 'igniter::admin.text_set_status',
                'iconCssClass' => 'fa fa-circle fa-fw text-'.UserState::forUser()->getStatusColorName(),
                'attributes' => [
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#editStaffStatusModal',
                    'role' => 'button',
                ],
            ],
            'account' => [
                'label' => 'igniter::admin.text_edit_details',
                'iconCssClass' => 'fa fa-user fa-fw',
                'url' => admin_url('users/account'),
                'priority' => 20,
            ],
            'logout' => [
                'label' => 'igniter::admin.text_logout',
                'cssClass' => 'text-danger',
                'iconCssClass' => 'fa fa-power-off fa-fw',
                'url' => admin_url('logout'),
            ],
        ]);

        // @deprecated namespaced event, remove before v5
        event('admin.menu.extendUserMenuLinks', [$items]);
        ExtendUserMenuLinks::dispatch($items);

        $instance = self::forUser();

        return self::$menuLinksCache = $items
            ->mapWithKeys(function ($item, $code) {
                $item = array_merge([
                    'priority' => 999,
                    'label' => null,
                    'cssClass' => null,
                    'iconCssClass' => null,
                    'attributes' => [],
                    'permission' => null,
                ], $item);

                if (array_key_exists('url', $item)) {
                    $item['attributes']['href'] = $item['url'];
                }

                return [
                    $code => (object)$item,
                ];
            })
            ->filter(function ($item) use ($instance) {
                if (!$permission = array_get($item, 'permission'))
                    return true;

                return $instance->user->hasPermission($permission);
            })
            ->sortBy('priority');
    }

    public static function listLocations($menu, $item, $user)
    {
        $instance = self::forUser();

        return AdminLocation::listLocations()->map(function ($location) use ($instance) {
            return (object)[
                'id' => $location->location_id,
                'name' => $location->location_name,
                'active' => $location->location_id === optional($instance->location)->location_id,
            ];
        });
    }

    public function getUserName()
    {
        return $this->user->name;
    }

    public function getLocationName()
    {
        return optional($this->location)->location_name;
    }

    public function getAvatarUrl()
    {
        return $this->user->avatar_url;
    }

    public function hasActiveLocation()
    {
        return AdminLocation::check();
    }

    public function listGroupNames()
    {
        return $this->user->groups->pluck('user_group_name')->all();
    }

    public function getRoleName()
    {
        return optional($this->user->role)->name;
    }
}
