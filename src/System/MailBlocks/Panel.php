<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

class Panel extends PartialBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'panel',
            'name' => 'igniter::system.mail_builder.block_panel',
            'description' => 'igniter::system.mail_builder.block_panel_description',
            'icon' => 'fa fa-square',
        ];
    }

    public function partialCode(): string
    {
        return 'panel';
    }
}
