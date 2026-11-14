<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

class ContentSlot extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'conditional' => false,
            'code' => 'content_slot',
            'name' => 'igniter::system.mail_builder.block_content_slot',
            'description' => 'igniter::system.mail_builder.block_content_slot_description',
            'icon' => 'fa fa-inbox',
            'category' => 'layout',
            'modes' => [CompileContext::MODE_LAYOUT],
            'unique' => true,
            'required' => [CompileContext::MODE_LAYOUT],
            'row' => true,
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->join(
            $writer->static('<tr><td class="body" width="100%" cellpadding="0" cellspacing="0">'
                .'<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0">'
                .'<tr><td class="content-cell">'),
            $writer->slot(),
            $writer->static('</td></tr></table></td></tr>'),
        );
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->slot();
    }
}
