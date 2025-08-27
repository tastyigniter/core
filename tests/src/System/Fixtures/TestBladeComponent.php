<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Fixtures;

use Igniter\Main\Traits\ConfigurableComponent;
use Illuminate\View\Component;
use Override;

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

    #[Override]
    public function render(): string
    {
        return '<div>Test Component</div>';
    }
}
