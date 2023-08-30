<?php

namespace Igniter\Flame\Translation\Contracts;

interface Driver
{
    /**
     * @return mixed
     */
    public function load($locale, $group, $namespace = null);
}
