<?php

declare(strict_types=1);

namespace Igniter\Tests\Main\Fixtures;

use Igniter\Main\Classes\BaseBlock;
use Override;

class TestTextBlock extends BaseBlock
{
    #[Override]
    public function blockDetails(): array
    {
        return [
            'code' => 'text',
            'name' => 'Text',
            'description' => 'A text block',
        ];
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'title' => ['label' => 'Title', 'type' => 'text', 'default' => 'Hello', 'validationRule' => 'max:20'],
            'body' => ['label' => 'Body', 'type' => 'textarea'],
        ];
    }

    #[Override]
    public function resolve(array $settings): array
    {
        return ['upper' => strtoupper((string)$settings['title'])];
    }
}
