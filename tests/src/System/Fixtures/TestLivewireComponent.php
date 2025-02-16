<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Fixtures;

use Igniter\Main\Traits\ConfigurableComponent;
use Livewire\Component;

class TestLivewireComponent extends Component
{
    use ConfigurableComponent;

    public static function componentMeta(): array
    {
        return [
            'code' => 'test::livewire-component',
            'name' => 'Test Livewire Component',
            'description' => 'Test livewire component description',
        ];
    }
}
