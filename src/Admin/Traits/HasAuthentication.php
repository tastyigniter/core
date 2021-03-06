<?php

namespace Igniter\Admin\Traits;

use Igniter\Admin\Facades\AdminAuth;

/**
 * Has Authentication Trait Class
 */
trait HasAuthentication
{
    /**
     * @var bool If TRUE, this class requires the user to be logged in before
     * accessing any method.
     */
    protected $requireAuthentication = true;

    /**
     * @var \Igniter\Admin\Models\User Stores the logged in admin user model.
     */
    protected $currentUser;

    public function checkUser()
    {
        return AdminAuth::check();
    }

    public function setUser($currentUser)
    {
        $this->currentUser = $currentUser;
    }

    public function getUser()
    {
        return $this->currentUser;
    }
}
