<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

class Image extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'image',
            'name' => 'igniter::system.mail_builder.block_image',
            'icon' => 'fa fa-image',
        ];
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'src' => [
                'label' => 'igniter::system.mail_builder.label_image',
                'type' => 'image',
                'validationRule' => ['required', 'string', 'max:2000', 'regex:/^(https?:\/\/|\/|\{\{\s*\$\w+)/i'],
            ],
            'alt' => [
                'label' => 'igniter::system.mail_builder.label_alt',
                'type' => 'text',
                'validationRule' => ['nullable', 'string', 'max:255'],
            ],
            'width' => [
                'label' => 'igniter::system.mail_builder.label_width',
                'type' => 'number',
                'default' => 570,
                'min' => 16,
                'max' => 570,
                'span' => 'left',
                'validationRule' => ['nullable', 'integer', 'between:16,570'],
            ],
            'align' => [
                'label' => 'igniter::system.mail_builder.label_align',
                'type' => 'select',
                'default' => 'center',
                'options' => Heading::alignOptions(),
                'span' => 'right',
                'validationRule' => ['required', 'in:left,center,right'],
            ],
            'link' => [
                'label' => 'igniter::system.mail_builder.label_link',
                'type' => 'url',
                'placeholder' => 'https://',
                'validationRule' => ['nullable', 'string', 'max:2000'],
            ],
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        $src = $writer->url($settings['src'] ?? '');
        if ($src->isEmpty()) {
            return $writer->none();
        }

        $width = $writer->number($settings['width'] ?? null, 16, 570, 570);
        $image = $writer->tag('img', [
            'src' => $src,
            'alt' => $writer->attr($settings['alt'] ?? ''),
            'width' => $width,
            'style' => $writer->join($writer->static('max-width: 100%; height: auto; width: '), $width, $writer->static('px')),
        ], void: true);

        $link = $writer->url($settings['link'] ?? '');
        if (!$link->isEmpty()) {
            $image = $writer->tag('a', ['href' => $link, 'target' => '_blank'], $image);
        }

        return $writer->tag('p', ['style' => $writer->static('text-align: '.Heading::align($settings['align'] ?? null))], $image);
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        $alt = $writer->text($settings['alt'] ?? '');

        return $alt->isEmpty() ? $writer->none() : $writer->join($writer->static('['), $alt, $writer->static(']'));
    }

    #[Override]
    public function summary(array $settings): string
    {
        return (string)($settings['alt'] ?? '') ?: basename((string)($settings['src'] ?? ''));
    }
}
