<?php

declare(strict_types=1);

namespace Igniter\Tests\System\Fixtures;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Override;

class TestMailBlock extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'test.extension::greeting',
            'name' => 'Greeting',
            'modes' => [CompileContext::MODE_TEMPLATE],
        ];
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'name' => [
                'label' => 'Name',
                'type' => 'text',
                'default' => 'Guest',
                'validationRule' => ['required', 'string'],
            ],
        ];
    }

    #[Override]
    public function appliesTo(CompileContext $context): bool
    {
        return $context->code !== 'excluded';
    }

    #[Override]
    public function assets(): array
    {
        return ['test-mail-block.js'];
    }

    #[Override]
    public function preview(array $settings): array
    {
        return ['greeting_name' => 'Preview guest'];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->tag('p', [], $writer->join($writer->static('Hello '), $writer->text($settings['name'] ?? '')));
    }
}
