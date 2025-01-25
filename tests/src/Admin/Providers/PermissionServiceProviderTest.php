<?php

namespace Igniter\Tests\Admin\Providers;

use Igniter\User\Classes\PermissionManager;

it('registers Admin permissions', function() {
    $permissionManager = resolve(PermissionManager::class);

    $permissions = $permissionManager->listPermissions();

    expect(array_first($permissions, fn($permission) => $permission->code === 'Admin.Dashboard')->group)
        ->toBe('igniter::admin.permissions.name')
        ->and(array_first($permissions, fn($permission) => $permission->code === 'Admin.Statuses')->group)
        ->toBe('igniter::admin.permissions.name');
});
