<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Fixtures;

use Igniter\Main\Traits\ConfigurableComponent;
use Livewire\Component;

class TestBlockLivewireComponent extends Component
{
    use ConfigurableComponent;

    public string $title = 'Default title';

    public static function componentMeta(): array
    {
        return [
            'code' => 'test::block-livewire',
            'name' => 'Block Livewire Component',
            'description' => 'Livewire component used as a page builder block',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'title' => [
                'label' => 'Title',
                'type' => 'text',
            ],
        ];
    }

    public function render(): string
    {
        return <<<'blade'
            <div class="block-livewire">{{ $title }}</div>
        blade;
    }
}
