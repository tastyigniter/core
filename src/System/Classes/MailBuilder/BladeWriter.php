<?php

declare(strict_types=1);

namespace Igniter\System\Classes\MailBuilder;

use Closure;
use Igniter\Flame\Exception\SystemException;
use Illuminate\Support\Str;

/**
 * Builds the compiled Blade of a mail builder block.
 *
 * Every value goes through a helper that escapes it, so the only Blade in the
 * output is the fixed structure the writer produces: `{{ $variable }}` echoes
 * of allowed variables, `@partial` includes and `@foreach` loops over
 * registered arrays. Blade syntax typed into a setting is written as plain
 * text: `{`, `}` and `@` are written as HTML entities.
 */
class BladeWriter
{
    protected const string TOKEN_PATTERN = '/\{\{\s*\$([A-Za-z_]\w*)(?:\[\'(\w+)\'\])?\s*\}\}/';

    protected const string SAFE_URL_PATTERN = '/^(https?:\/\/|mailto:|#|\/)/i';

    /** Blade syntax a static() string may not contain. */
    protected const array BLADE_SYNTAX = ['{{', '{!!', '@', '<?', '?>'];

    protected Closure $makeFragment;

    public function __construct(protected CompileContext $context)
    {
        // BladeFragment has a private constructor, only the writer creates fragments
        $this->makeFragment = Closure::bind(static fn(string $blade): BladeFragment => new BladeFragment($blade), null, BladeFragment::class);
    }

    public function getContext(): CompileContext
    {
        return $this->context;
    }

    public function isPlain(): bool
    {
        return false;
    }

    //
    // Values
    //

    /**
     * Escaped text. Allowed `{{ $variable }}` tokens are kept as variables,
     * anything else is written as plain text.
     */
    public function text(mixed $value): BladeFragment
    {
        return $this->fragment($this->compileTokens($this->stringValue($value), $this->escape(...)));
    }

    /**
     * Markdown converted to HTML. HTML in the value is escaped, allowed
     * `{{ $variable }}` tokens are kept as variables.
     */
    public function markdown(mixed $value): BladeFragment
    {
        $value = $this->stringValue($value);
        if (trim($value) === '') {
            return $this->none();
        }

        $nonce = Str::lower(Str::random(12));
        $tokens = [];
        $source = preg_replace_callback(self::TOKEN_PATTERN, function(array $matches) use (&$tokens, $nonce): string {
            $placeholder = 'mbvar'.$nonce.'x'.count($tokens).'y';
            $tokens[$placeholder] = $this->compileToken($matches[1], $matches[2] ?? '', $matches[0], $this->escape(...));

            return $placeholder;
        }, $value);

        $html = (string)Str::markdown((string)$source, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        // Neutralise Blade syntax left in the converted text, then restore the tokens
        $html = strtr($html, ['{' => '&#123;', '}' => '&#125;', '@' => '&#64;']);
        $html = strtr($html, $tokens);

        return $this->fragment($this->singleLine($html));
    }

    /**
     * An escaped HTML attribute value.
     */
    public function attr(mixed $value): BladeFragment
    {
        return $this->text($value);
    }

    /**
     * A link URL. Only http, https, mailto, relative and anchor URLs or a URL
     * starting with an allowed variable are kept, anything else is dropped.
     */
    public function url(mixed $value): BladeFragment
    {
        $value = trim($this->stringValue($value));
        if ($value === '') {
            return $this->none();
        }

        $startsWithVariable = preg_match('/^\{\{\s*\$([A-Za-z_]\w*)(?:\[\'(\w+)\'\])?\s*\}\}/', $value, $matches)
            && $this->isAllowedToken($matches[1], $matches[2] ?? '');

        if (!$startsWithVariable && !preg_match(self::SAFE_URL_PATTERN, $value)) {
            return $this->none();
        }

        return $this->text($value);
    }

    /**
     * A hex colour, or the default when the value is not one.
     */
    public function color(mixed $value, string $default = ''): BladeFragment
    {
        $value = $this->stringValue($value);

        if (!preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
            $value = preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $default) ? $default : '';
        }

        return $this->fragment($value);
    }

    /**
     * A whole number clamped between min and max.
     */
    public function number(mixed $value, int $min = 0, int $max = PHP_INT_MAX, int $default = 0): BladeFragment
    {
        $number = is_numeric($value) ? (int)$value : $default;

        return $this->fragment((string)max($min, min($max, $number)));
    }

    /**
     * Echoes an allowed variable.
     */
    public function var(string $name): BladeFragment
    {
        throw_unless($this->context->allowsVariable($name), new SystemException(sprintf(
            'Mail variable [%s] is not registered.', $name,
        )));

        return $this->fragment(sprintf('{{ $%s }}', $name));
    }

    /**
     * Echoes a field of the item in the loop being compiled.
     */
    public function item(string $item, string $field): BladeFragment
    {
        throw_unless($this->context->allowsItemField($item, $field), new SystemException(sprintf(
            'Mail variable [%s.%s] is not available here.', $item, $field,
        )));

        $format = $this->context->isRawItemField($item, $field) ? "{!! \$%s['%s'] !!}" : "{{ \$%s['%s'] }}";

        return $this->fragment(sprintf($format, $item, $field));
    }

    /**
     * Echoes the content slot: the template body in a layout or the slot of a partial.
     */
    public function slot(): BladeFragment
    {
        if ($this->context->isMode(CompileContext::MODE_LAYOUT)) {
            return $this->fragment('{!! $body !!}');
        }

        throw_unless($this->context->isMode(CompileContext::MODE_PARTIAL), new SystemException(
            'The content slot is only available in layouts and partials.',
        ));

        return $this->fragment('{!! $slot !!}');
    }

    //
    // Structure
    //

    /**
     * Includes a mail partial with parameters and an optional slot.
     *
     * @param array<string, mixed> $params Parameter values, which may contain `{{ $variable }}` tokens
     */
    public function partial(string $code, array $params = [], ?BladeFragment $slot = null): BladeFragment
    {
        throw_unless(preg_match('/^[\w.:-]+$/', $code) && $this->context->allowsPartial($code), new SystemException(
            sprintf('Mail partial [%s] is not available.', $code),
        ));

        $arguments = [];
        foreach ($params as $name => $value) {
            if (!preg_match('/^[A-Za-z_]\w*$/', (string)$name) || in_array($name, ['slot', 'body'], true)) {
                continue;
            }

            $arguments[] = sprintf("'%s' => %s", $name, $this->phpExpression($this->stringValue($value)));
        }

        $expression = sprintf("'%s'", $code).($arguments !== [] ? ', ['.implode(', ', $arguments).']' : '');

        return $this->fragment(sprintf(' @partial(%s)%s @endpartial', $expression, $slot?->toBlade() ?? ''));
    }

    /**
     * Repeats the content for each item of a registered array variable.
     *
     * @param callable(BladeWriter, string): BladeFragment $callback Receives the writer and the item variable name
     */
    public function loop(string $array, callable $callback): BladeFragment
    {
        $item = $this->context->variables->itemName($array);

        throw_unless($item && $this->context->variables->isArray($array), new SystemException(sprintf(
            'Mail variable [%s] is not a registered array.', $array,
        )));

        $this->context->enterLoop($array, $item);

        try {
            $content = $callback($this, $item);
        } finally {
            $this->context->leaveLoop();
        }

        return $this->fragment(sprintf(' @foreach($%s as $%s)%s @endforeach', $array, $item, $content->toBlade()));
    }

    /**
     * Includes the content only when the variable has a value.
     */
    public function ifSet(string $name, BladeFragment $content): BladeFragment
    {
        $this->var($name);

        return $this->fragment(sprintf(' @if(!empty($%s))%s @endif', $name, $content->toBlade()));
    }

    /**
     * Includes the content only when the variable has no value.
     */
    public function ifEmpty(string $name, BladeFragment $content): BladeFragment
    {
        $this->var($name);

        return $this->fragment(sprintf(' @if(empty($%s))%s @endif', $name, $content->toBlade()));
    }

    /**
     * Fixed markup written by the block class. It may not contain Blade syntax:
     * dynamic values must go through the other helpers.
     */
    public function static(string $html): BladeFragment
    {
        foreach (self::BLADE_SYNTAX as $syntax) {
            throw_if(str_contains($html, $syntax), new SystemException(sprintf(
                'Static mail markup may not contain Blade syntax [%s]. Use the BladeWriter helpers for dynamic values.', $syntax,
            )));
        }

        return $this->fragment($html);
    }

    /**
     * An element with attributes. String attribute values are escaped.
     *
     * @param array<string, string|BladeFragment|null> $attributes
     */
    public function tag(string $tag, array $attributes = [], ?BladeFragment $content = null, bool $void = false): BladeFragment
    {
        throw_unless(preg_match('/^[a-z][a-z0-9]*$/', $tag), new SystemException(sprintf('Invalid tag name [%s].', $tag)));

        $html = '<'.$tag;
        foreach ($attributes as $name => $value) {
            throw_unless(preg_match('/^[a-z][a-z0-9-]*$/', (string)$name), new SystemException(sprintf('Invalid attribute name [%s].', $name)));

            if (is_null($value) || ($value instanceof BladeFragment && $value->isEmpty() && $name !== 'alt')) {
                continue;
            }

            $html .= sprintf(' %s="%s"', $name, $value instanceof BladeFragment ? $value->toBlade() : $this->escape((string)$value));
        }

        if ($void) {
            return $this->fragment($html.'>');
        }

        return $this->fragment($html.'>'.($content?->toBlade() ?? '').'</'.$tag.'>');
    }

    /**
     * Joins fragments.
     */
    public function join(BladeFragment ...$fragments): BladeFragment
    {
        return $this->joinWith('', ...$fragments);
    }

    public function joinWith(string $separator, BladeFragment ...$fragments): BladeFragment
    {
        $separator = $this->static($separator)->toBlade();

        return $this->fragment(implode($separator, array_map(
            fn(BladeFragment $fragment): string => $fragment->toBlade(),
            array_filter($fragments, fn(BladeFragment $fragment): bool => !$fragment->isEmpty()),
        )));
    }

    public function none(): BladeFragment
    {
        return $this->fragment('');
    }

    //
    // Internals
    //

    protected function fragment(string $blade): BladeFragment
    {
        return ($this->makeFragment)($blade);
    }

    protected function escape(string $value): string
    {
        return strtr(htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), [
            '{' => '&#123;',
            '}' => '&#125;',
            '@' => '&#64;',
        ]);
    }

    protected function compileTokens(string $value, callable $escape): string
    {
        $result = '';
        $offset = 0;
        preg_match_all(self::TOKEN_PATTERN, $value, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($matches as $match) {
            [$token, $position] = $match[0];
            $result .= $escape(substr($value, $offset, $position - $offset));
            $result .= $this->compileToken($match[1][0], $match[2][0] ?? '', $token, $escape);
            $offset = $position + strlen($token);
        }

        return $result.$escape(substr($value, $offset));
    }

    protected function compileToken(string $name, string $field, string $token, callable $escape): string
    {
        if ($field === '') {
            return $this->context->allowsVariable($name) ? sprintf('{{ $%s }}', $name) : $escape($token);
        }

        if (!$this->context->allowsItemField($name, $field)) {
            return $escape($token);
        }

        return $this->context->isRawItemField($name, $field)
            ? sprintf("{!! \$%s['%s'] !!}", $name, $field)
            : sprintf("{{ \$%s['%s'] }}", $name, $field);
    }

    protected function isAllowedToken(string $name, string $field): bool
    {
        return $field === ''
            ? $this->context->allowsVariable($name)
            : $this->context->allowsItemField($name, $field);
    }

    /**
     * A PHP expression for a partial parameter: literal text is base64 encoded,
     * so it can never break out of the expression, allowed variables are kept.
     */
    protected function phpExpression(string $value): string
    {
        $parts = [];
        $offset = 0;
        preg_match_all(self::TOKEN_PATTERN, $value, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($matches as $match) {
            [$token, $position] = $match[0];
            $parts[] = $this->literalExpression(substr($value, $offset, $position - $offset));

            $name = $match[1][0];
            $field = $match[2][0] ?? '';
            $parts[] = match (true) {
                $field === '' && $this->context->allowsVariable($name) => '$'.$name,
                $field !== '' && $this->context->allowsItemField($name, $field) => sprintf("\$%s['%s']", $name, $field),
                default => $this->literalExpression($token),
            };

            $offset = $position + strlen($token);
        }

        $parts[] = $this->literalExpression(substr($value, $offset));
        $parts = array_values(array_filter($parts, fn(string $part): bool => $part !== ''));

        return $parts !== [] ? implode(' . ', $parts) : "''";
    }

    protected function literalExpression(string $value): string
    {
        return $value === '' ? '' : sprintf("base64_decode('%s')", base64_encode($value));
    }

    protected function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }

    /**
     * Keeps a fragment on one line, so the Markdown pass over template bodies
     * treats it as a single HTML block.
     */
    protected function singleLine(string $html): string
    {
        return trim((string)preg_replace('/\s*\n\s*/', ' ', $html));
    }
}
