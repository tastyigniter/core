<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Override;

class Spacer extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'spacer',
            'name' => 'igniter::system.mail_builder.block_spacer',
            'icon' => 'fa fa-arrows-up-down',
            'category' => 'layout',
        ];
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'height' => [
                'label' => 'igniter::system.mail_builder.label_height',
                'type' => 'number',
                'default' => 24,
                'min' => 4,
                'max' => 200,
                'validationRule' => ['required', 'integer', 'between:4,200'],
            ],
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        $height = $writer->number($settings['height'] ?? null, 4, 200, 24);

        return $writer->tag('div', [
            'style' => $writer->join(
                $writer->static('height: '), $height, $writer->static('px; line-height: '), $height, $writer->static('px;'),
            ),
        ], $writer->static('&nbsp;'));
    }

    #[Override]
    public function summary(array $settings): string
    {
        return ($settings['height'] ?? 24).'px';
    }
}
