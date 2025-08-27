<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Fixtures;

use Igniter\System\Classes\BaseComponent;
use Override;

class TestComponentWithLifecycle extends BaseComponent
{
    public static function componentMeta(): array
    {
        return [
            'code' => 'testComponentWithLifecycle',
            'name' => 'Test Component With Lifecycle',
            'description' => 'Test component description with lifecycle methods',
        ];
    }

    #[Override]
    public function onRun()
    {
        return redirect()->to('http://localhost');
    }
}
