<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Fixtures;

use Igniter\Main\Traits\ConfigurableComponent;
use Illuminate\View\Component;
use Override;

class TestBlockBladeComponent extends Component
{
    use ConfigurableComponent;

    public function __construct(public string $title = 'Default title', public int $itemsPerRow = 3) {}

    public static function componentMeta(): array
    {
        return [
            'code' => 'test::block-blade',
            'name' => 'Block Blade Component',
            'description' => 'Blade component used as a page builder block',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'title' => [
                'label' => 'Title',
                'type' => 'text',
            ],
            'itemsPerRow' => [
                'label' => 'Items per row',
                'type' => 'number',
            ],
        ];
    }

    #[Override]
    public function render(): string
    {
        return '<div class="block-blade">{{ $title }}-{{ $itemsPerRow }}</div>';
    }
}
