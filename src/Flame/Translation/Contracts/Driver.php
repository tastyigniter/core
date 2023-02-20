<?php

namespace Igniter\Flame\Translation\Contracts;

interface Driver
{
    /**
     * @param null $namespace
     *
     * @return mixed
     */
    public function load($locale, $group, $namespace = null);
}
