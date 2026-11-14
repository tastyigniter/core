<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

class Header extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'header',
            'name' => 'igniter::system.mail_builder.block_header',
            'icon' => 'fa fa-window-maximize',
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
            'show_logo' => [
                'label' => 'igniter::system.mail_builder.label_show_logo',
                'type' => 'switch',
                'default' => true,
            ],
            'text' => [
                'label' => 'igniter::system.mail_builder.label_text',
                'type' => 'text',
                'default' => '{{ $site_name }}',
                'comment' => 'igniter::system.mail_builder.help_header_text',
                'validationRule' => ['nullable', 'string', 'max:255'],
            ],
            'url' => [
                'label' => 'igniter::system.mail_builder.label_link',
                'type' => 'url',
                'placeholder' => 'https://',
                'validationRule' => ['nullable', 'string', 'max:2000'],
            ],
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        $text = $writer->text($settings['text'] ?? '');
        if (!empty($settings['show_logo']) && $context->allowsVariable('mail_logo_url')) {
            $logo = $writer->tag('img', [
                'src' => $writer->var('mail_logo_url'),
                'alt' => $text,
                'style' => $writer->static('max-height: 90px;'),
            ], void: true);
            $text = $writer->ifSet('mail_logo_url', $logo);
        }

        $url = $writer->url($settings['url'] ?? '');
        $params = $url->isEmpty() ? [] : ['url' => (string)$settings['url']];

        if ($context->allowsPartial('header')) {
            return $writer->partial('header', $params, $text);
        }

        return $writer->tag('tr', [], $writer->tag('td', ['class' => 'header'], $text));
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->text($settings['text'] ?? '');
    }
}
