<?php

declare(strict_types=1);

namespace Igniter\Tests\Fixtures\Actions;

use Igniter\System\Classes\ControllerAction;

class TestControllerAction extends ControllerAction
{
    public $testProperty = 'value';

    public static function testStaticFunction(): string
    {
        return 'staticResult';
    }

    public function testFunction(): string
    {
        return 'result';
    }
}
