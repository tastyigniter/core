<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

class Footer extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'footer',
            'name' => 'igniter::system.mail_builder.block_footer',
            'icon' => 'fa fa-window-minimize',
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
            'content' => [
                'label' => 'igniter::system.mail_builder.label_content',
                'type' => 'markdown',
                'default' => '© {{ $site_name }}. All rights reserved.',
                'validationRule' => ['nullable', 'string', 'max:5000'],
            ],
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        $content = $writer->markdown($settings['content'] ?? '');

        if ($context->allowsPartial('footer')) {
            return $writer->partial('footer', [], $content);
        }

        return $writer->tag('tr', [], $writer->tag('td', ['class' => 'footer', 'align' => 'center'], $content));
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->join($writer->static("-------------------\n"), $writer->markdown($settings['content'] ?? ''));
    }
}
