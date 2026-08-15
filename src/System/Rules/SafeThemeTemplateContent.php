<?php

declare(strict_types=1);

namespace Igniter\System\Rules;

use Closure;
use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Pagic\SandboxProfile;
use Igniter\Flame\Pagic\TemplateSandbox;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeThemeTemplateContent implements ValidationRule
{
    protected bool $wrapInPhpTags = false;

    public function wrapInPhpTags(): self
    {
        $this->wrapInPhpTags = true;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }

        try {
            if ($this->wrapInPhpTags) {
                $value = $this->wrapCodeInPhpTags($value);
            }

            resolve(TemplateSandbox::class)->assertSafe($value, SandboxProfile::Theme);
        } catch (SystemException $systemException) {
            $fail($systemException->getMessage());
        }
    }

    public function wrapCodeInPhpTags(string $code): string
    {
        if (preg_match('/^\s*<\?(?:php|=)/i', $code)) {
            return $code;
        }

        return '<?php'.PHP_EOL.$code.PHP_EOL.'?>';
    }
}
