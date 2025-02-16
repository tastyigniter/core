<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Fixtures;

class TestComponentWithLifecycle extends \Igniter\System\Classes\BaseComponent
{
    public static function componentMeta(): array
    {
        return [
            'code' => 'testComponentWithLifecycle',
            'name' => 'Test Component With Lifecycle',
            'description' => 'Test component description with lifecycle methods',
        ];
    }

    public function onRun()
    {
        return redirect()->to('http://localhost');
    }
}
