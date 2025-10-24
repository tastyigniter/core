<?php

declare(strict_types=1);

namespace Igniter\Tests\Admin\Providers;

use Igniter\User\Classes\PermissionManager;
use Illuminate\Support\Arr;

it('registers Admin permissions', function() {
    $permissionManager = resolve(PermissionManager::class);

    $permissions = $permissionManager->listPermissions();

    expect(Arr::first($permissions, fn($permission) => $permission->code === 'Admin.Dashboard')->group)
        ->toBe('igniter::admin.permissions.name')
        ->and(Arr::first($permissions, fn($permission) => $permission->code === 'Admin.Statuses')->group)
        ->toBe('igniter::admin.permissions.name');
});
