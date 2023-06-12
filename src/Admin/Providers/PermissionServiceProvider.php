<?php

namespace Igniter\Admin\Providers;

use Igniter\Admin\Classes\PermissionManager;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function register()
    {
        resolve(PermissionManager::class)->registerCallback(function ($manager) {
            $manager->registerPermissions('Admin', [
                'Admin.Dashboard' => [
                    'label' => 'igniter::admin.permissions.dashboard', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Allergens' => [
                    'label' => 'igniter::admin.permissions.allergens', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Categories' => [
                    'label' => 'igniter::admin.permissions.categories', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Menus' => [
                    'label' => 'igniter::admin.permissions.menus', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Mealtimes' => [
                    'label' => 'igniter::admin.permissions.mealtimes', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Locations' => [
                    'label' => 'igniter::admin.permissions.locations', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Tables' => [
                    'label' => 'igniter::admin.permissions.tables', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Orders' => [
                    'label' => 'igniter::admin.permissions.orders', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.DeleteOrders' => [
                    'label' => 'igniter::admin.permissions.delete_orders', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.AssignOrders' => [
                    'label' => 'igniter::admin.permissions.assign_orders', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Reservations' => [
                    'label' => 'igniter::admin.permissions.reservations', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.DeleteReservations' => [
                    'label' => 'igniter::admin.permissions.delete_reservations', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.AssignReservations' => [
                    'label' => 'igniter::admin.permissions.assign_reservations', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Payments' => [
                    'label' => 'igniter::admin.permissions.payments', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.CustomerGroups' => [
                    'label' => 'igniter::admin.permissions.customer_groups', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Customers' => [
                    'label' => 'igniter::admin.permissions.customers', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Impersonate' => [
                    'label' => 'igniter::admin.permissions.impersonate_staff', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.ImpersonateCustomers' => [
                    'label' => 'igniter::admin.permissions.impersonate_customers', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.StaffGroups' => [
                    'label' => 'igniter::admin.permissions.user_groups', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Staffs' => [
                    'label' => 'igniter::admin.permissions.staffs', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Statuses' => [
                    'label' => 'igniter::admin.permissions.statuses', 'group' => 'igniter::admin.permissions.name',
                ],
            ]);
        });
    }
}
