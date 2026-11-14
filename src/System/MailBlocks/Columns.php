<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

class Columns extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'columns',
            'name' => 'igniter::system.mail_builder.block_columns',
            'icon' => 'fa fa-table-columns',
            'category' => 'layout',
        ];
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'left' => [
                'label' => 'igniter::system.mail_builder.label_left_column',
                'type' => 'markdown',
                'validationRule' => ['nullable', 'string', 'max:10000'],
            ],
            'right' => [
                'label' => 'igniter::system.mail_builder.label_right_column',
                'type' => 'markdown',
                'validationRule' => ['nullable', 'string', 'max:10000'],
            ],
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        $cell = fn(string $key): BladeFragment => $writer->tag('td', [
            'width' => '50%',
            'valign' => 'top',
            'style' => $writer->static('padding: 0 8px;'),
        ], $writer->markdown($settings[$key] ?? ''));

        return $writer->tag('table', ['width' => '100%', 'cellpadding' => '0', 'cellspacing' => '0'],
            $writer->tag('tr', [], $writer->join($cell('left'), $cell('right'))),
        );
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->lines($writer->markdown($settings['left'] ?? ''), $writer->markdown($settings['right'] ?? ''));
    }
}
