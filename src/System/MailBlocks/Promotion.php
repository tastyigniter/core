<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

class Promotion extends PartialBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'promotion',
            'name' => 'igniter::system.mail_builder.block_promotion',
            'description' => 'igniter::system.mail_builder.block_promotion_description',
            'icon' => 'fa fa-bullhorn',
        ];
    }

    public function partialCode(): string
    {
        return 'promotion';
    }
}
