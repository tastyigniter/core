<?php

namespace Igniter\Tests\Fixtures\Actions;

use Igniter\System\Classes\ControllerAction;

class TestControllerAction extends ControllerAction
{
    public $testProperty = 'value';

    public static function testStaticFunction()
    {
        return 'staticResult';
    }

    public function testFunction()
    {
        return 'result';
    }
}
