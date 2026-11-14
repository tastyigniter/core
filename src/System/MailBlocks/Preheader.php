<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Override;

class Preheader extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'preheader',
            'name' => 'igniter::system.mail_builder.block_preheader',
            'description' => 'igniter::system.mail_builder.block_preheader_description',
            'icon' => 'fa fa-eye-slash',
            'category' => 'layout',
            'modes' => [CompileContext::MODE_LAYOUT],
            'unique' => true,
            'row' => true,
        ];
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'text' => [
                'label' => 'igniter::system.mail_builder.label_text',
                'type' => 'text',
                'validationRule' => ['nullable', 'string', 'max:255'],
            ],
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->tag('tr', [], $writer->tag('td', [
            'style' => $writer->static('display: none; max-height: 0; overflow: hidden; mso-hide: all;'),
        ], $writer->text($settings['text'] ?? '')));
    }
}
