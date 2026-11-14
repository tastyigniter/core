<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

/**
 * A block that wraps Markdown content in one of the core mail partials, such
 * as the panel, so it is styled by the mail theme. Outputs nothing when the
 * partial is not available.
 */
abstract class PartialBlock extends BaseMailBlock
{
    /**
     * The code of the mail partial wrapping the content.
     */
    abstract public function partialCode(): string;

    #[Override]
    public function defineFields(): array
    {
        return [
            'content' => [
                'label' => 'igniter::system.mail_builder.label_content',
                'type' => 'markdown',
                'comment' => 'igniter::system.mail_builder.help_markdown',
                'default' => 'Write your message here.',
                'validationRule' => ['nullable', 'string', 'max:10000'],
            ],
        ];
    }

    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        return $this->wrap($writer->markdown($settings['content'] ?? ''), $writer, $context);
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        return $this->wrap($writer->markdown($settings['content'] ?? ''), $writer, $context);
    }

    protected function wrap(BladeFragment $content, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        if ($content->isEmpty() || !$context->allowsPartial($this->partialCode())) {
            return $writer->none();
        }

        return $writer->partial($this->partialCode(), [], $content);
    }
}
