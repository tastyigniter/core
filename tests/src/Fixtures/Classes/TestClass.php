<?php

namespace Igniter\Tests\Fixtures\Classes;

use BadMethodCallException;

class TestClass
{
    public static function __callStatic(string $name, array $arguments)
    {
        throw new BadMethodCallException('Method '.$name.' is not defined.');
    }
}
