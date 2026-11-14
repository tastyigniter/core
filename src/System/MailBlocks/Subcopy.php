<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

class Subcopy extends PartialBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'subcopy',
            'name' => 'igniter::system.mail_builder.block_subcopy',
            'description' => 'igniter::system.mail_builder.block_subcopy_description',
            'icon' => 'fa fa-text-height',
        ];
    }

    public function partialCode(): string
    {
        return 'subcopy';
    }
}
