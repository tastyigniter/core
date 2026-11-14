<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

class Heading extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'heading',
            'name' => 'igniter::system.mail_builder.block_heading',
            'icon' => 'fa fa-heading',
        ];
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'text' => [
                'label' => 'igniter::system.mail_builder.label_text',
                'type' => 'text',
                'default' => 'Heading',
                'validationRule' => ['required', 'string', 'max:255'],
            ],
            'level' => [
                'label' => 'igniter::system.mail_builder.label_heading_level',
                'type' => 'select',
                'default' => 'h1',
                'options' => ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3'],
                'span' => 'left',
                'validationRule' => ['required', 'in:h1,h2,h3'],
            ],
            'align' => [
                'label' => 'igniter::system.mail_builder.label_align',
                'type' => 'select',
                'default' => 'left',
                'options' => static::alignOptions(),
                'span' => 'right',
                'validationRule' => ['required', 'in:left,center,right'],
            ],
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        $level = in_array($settings['level'] ?? null, ['h1', 'h2', 'h3'], true) ? $settings['level'] : 'h1';

        return $writer->tag($level, [
            'style' => $writer->static('text-align: '.static::align($settings['align'] ?? null)),
        ], $writer->text($settings['text'] ?? ''));
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->text($settings['text'] ?? '');
    }

    public static function alignOptions(): array
    {
        return [
            'left' => 'igniter::system.mail_builder.text_align_left',
            'center' => 'igniter::system.mail_builder.text_align_center',
            'right' => 'igniter::system.mail_builder.text_align_right',
        ];
    }

    public static function align(mixed $value): string
    {
        return in_array($value, ['left', 'center', 'right'], true) ? $value : 'left';
    }
}
