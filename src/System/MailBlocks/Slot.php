<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

class Slot extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'conditional' => false,
            'code' => 'slot',
            'name' => 'igniter::system.mail_builder.block_slot',
            'description' => 'igniter::system.mail_builder.block_slot_description',
            'icon' => 'fa fa-inbox',
            'category' => 'layout',
            'modes' => [CompileContext::MODE_PARTIAL],
            'unique' => true,
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->slot();
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->slot();
    }
}
