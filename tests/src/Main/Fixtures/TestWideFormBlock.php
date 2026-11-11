<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Fixtures;

use Igniter\Main\Classes\BaseBlock;
use Override;

class TestWideFormBlock extends BaseBlock
{
    #[Override]
    public function blockDetails(): array
    {
        return [
            'code' => 'wide-form',
            'name' => 'Wide form',
            'unique' => true,
            'wide' => true,
            'pages' => ['checkout'],
        ];
    }
}
