<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

class Parameter extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'parameter',
            'name' => 'igniter::system.mail_builder.block_parameter',
            'description' => 'igniter::system.mail_builder.block_parameter_description',
            'icon' => 'fa fa-code',
            'category' => 'data',
            'modes' => [CompileContext::MODE_PARTIAL],
        ];
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'name' => [
                'label' => 'igniter::system.mail_builder.label_param',
                'type' => 'param',
                'validationRule' => ['required', 'string', 'regex:/^[A-Za-z_]\w*$/'],
            ],
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        $name = (string)($settings['name'] ?? '');

        return $context->allowsVariable($name) ? $writer->var($name) : $writer->none();
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        $name = (string)($settings['name'] ?? '');

        return $context->allowsVariable($name) ? $writer->var($name) : $writer->none();
    }
}
