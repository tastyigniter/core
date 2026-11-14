<?php

declare(strict_types=1);

namespace Igniter\System\Classes\MailBuilder;

use Override;
use Stringable;

/**
 * A piece of compiled mail Blade.
 *
 * Only BladeWriter can create fragments, so every fragment a block returns has
 * been built from escaped values and allowed variables.
 */
final readonly class BladeFragment implements Stringable
{
    private function __construct(private string $blade) {}

    public function isEmpty(): bool
    {
        return trim($this->blade) === '';
    }

    public function toBlade(): string
    {
        return $this->blade;
    }

    #[Override]
    public function __toString(): string
    {
        return $this->blade;
    }
}
