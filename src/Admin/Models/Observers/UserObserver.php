<?php

namespace Igniter\Admin\Models\Observers;

use Igniter\Admin\Models\User;

class UserObserver
{
    public function deleting(User $user)
    {
        $user->groups()->detach();
        $user->locations()->detach();
    }
}
