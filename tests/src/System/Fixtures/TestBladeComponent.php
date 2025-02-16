<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Fixtures;

use Igniter\Main\Traits\ConfigurableComponent;
use Illuminate\View\Component;

class TestBladeComponent extends Component
{
    use ConfigurableComponent;

    public static function componentMeta(): array
    {
        return [
            'code' => 'test::blade-component',
            'name' => 'Test Blade Component',
            'description' => 'Test blade component description',
        ];
    }

    public function render()
    {
        return '<div>Test Component</div>';
    }
}
