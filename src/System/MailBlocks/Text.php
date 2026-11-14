<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

class Text extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'text',
            'name' => 'igniter::system.mail_builder.block_text',
            'description' => 'igniter::system.mail_builder.block_text_description',
            'icon' => 'fa fa-align-left',
        ];
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'content' => [
                'label' => 'igniter::system.mail_builder.label_content',
                'type' => 'markdown',
                'comment' => 'igniter::system.mail_builder.help_markdown',
                'default' => 'Write your message here.',
                'validationRule' => ['nullable', 'string', 'max:20000'],
            ],
            'align' => [
                'label' => 'igniter::system.mail_builder.label_align',
                'type' => 'select',
                'default' => 'left',
                'options' => Heading::alignOptions(),
                'validationRule' => ['required', 'in:left,center,right'],
            ],
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        $content = $writer->markdown($settings['content'] ?? '');
        $align = Heading::align($settings['align'] ?? null);
        if ($content->isEmpty() || $align === 'left') {
            return $content;
        }

        return $writer->tag('div', ['style' => $writer->static('text-align: '.$align)], $content);
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->markdown($settings['content'] ?? '');
    }
}
