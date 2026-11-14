<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

class Divider extends BaseMailBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'divider',
            'name' => 'igniter::system.mail_builder.block_divider',
            'icon' => 'fa fa-minus',
            'category' => 'layout',
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->static('<hr style="border: 0; border-top: 1px solid #edeff2; margin: 24px 0;">');
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->static('----------');
    }
}
