<?php

declare(strict_types=1);

namespace Igniter\System\Classes\MailBuilder;

use Override;

/**
 * Builds the compiled plain-text Blade of a mail builder block.
 *
 * Works like BladeWriter, except Markdown is kept as written. Plain-text mail
 * is HTML entity decoded after rendering, so escaped text reads as typed.
 */
class TextWriter extends BladeWriter
{
    #[Override]
    public function isPlain(): bool
    {
        return true;
    }

    /**
     * Markdown is readable as plain text, so it is written as escaped text.
     */
    #[Override]
    public function markdown(mixed $value): BladeFragment
    {
        return $this->text($value);
    }

    /**
     * Text lines joined by blank lines.
     */
    public function lines(BladeFragment ...$fragments): BladeFragment
    {
        return $this->joinWith("\n\n", ...$fragments);
    }
}
