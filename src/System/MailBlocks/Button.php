<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

class Button extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'button',
            'name' => 'igniter::system.mail_builder.block_button',
            'icon' => 'fa fa-hand-pointer',
        ];
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'label' => [
                'label' => 'igniter::system.mail_builder.label_button_label',
                'type' => 'text',
                'default' => 'View details',
                'validationRule' => ['required', 'string', 'max:255'],
            ],
            'url' => [
                'label' => 'igniter::system.mail_builder.label_url',
                'type' => 'url',
                'placeholder' => 'https://',
                'validationRule' => ['required', 'string', 'max:2000', 'regex:/^(https?:\/\/|mailto:|#|\/|\{\{\s*\$\w+)/i'],
            ],
            'style' => [
                'label' => 'igniter::system.mail_builder.label_button_style',
                'type' => 'select',
                'default' => 'primary',
                'options' => [
                    'primary' => 'igniter::system.mail_builder.text_style_primary',
                    'success' => 'igniter::system.mail_builder.text_style_success',
                    'error' => 'igniter::system.mail_builder.text_style_error',
                ],
                'span' => 'left',
                'validationRule' => ['required', 'in:primary,success,error'],
            ],
            'align' => [
                'label' => 'igniter::system.mail_builder.label_align',
                'type' => 'select',
                'default' => 'center',
                'options' => Heading::alignOptions(),
                'span' => 'right',
                'validationRule' => ['required', 'in:left,center,right'],
            ],
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        if ($writer->url($settings['url'] ?? '')->isEmpty() || !$context->allowsPartial('button')) {
            return $writer->none();
        }

        return $writer->partial('button', [
            'url' => $settings['url'],
            'type' => in_array($settings['style'] ?? null, ['primary', 'success', 'error'], true) ? $settings['style'] : 'primary',
            'align' => in_array($settings['align'] ?? null, ['left', 'center', 'right'], true) ? $settings['align'] : 'center',
        ], $writer->text($settings['label'] ?? ''));
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        $url = $writer->url($settings['url'] ?? '');

        return $url->isEmpty() ? $writer->none() : $writer->joinWith(': ', $writer->text($settings['label'] ?? ''), $url);
    }
}
