<?php

declare(strict_types=1);

namespace Igniter\Main\Classes;

/**
 * HTML attributes entered for page builder sections and blocks, one per line:
 *
 *   data-track="hero"
 *   aria-label=Opening hours
 *   hidden
 *
 * Only safe attributes are kept: id, title, role, lang, dir, tabindex, style,
 * hidden and any data-* or aria-* attribute. Event handlers and links are
 * dropped, and values with script URLs are refused.
 */
class HtmlAttributes
{
    protected const array ALLOWED = ['id', 'title', 'role', 'lang', 'dir', 'tabindex', 'style', 'hidden'];

    protected const int MAX_VALUE_LENGTH = 500;

    /**
     * @return array<string, string>
     */
    public static function parse(mixed $text): array
    {
        if (!is_string($text) || trim($text) === '') {
            return [];
        }

        $attributes = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            if (!preg_match('/^\s*([A-Za-z][A-Za-z0-9_.:-]*)\s*(?:=\s*(?:"([^"]*)"|\'([^\']*)\'|(.*?)))?\s*$/', $line, $match)) {
                continue;
            }

            $name = strtolower($match[1]);
            $value = $match[2] ?? '';
            $value = $value !== '' ? $value : ($match[3] ?? '');
            $value = $value !== '' ? $value : ($match[4] ?? '');

            if (static::allowed($name, $value)) {
                $attributes[$name] = $value;
            }
        }

        return $attributes;
    }

    /**
     * The attributes of a block: its CSS class and the attributes entered for it.
     *
     * @return array<string, string>
     */
    public static function forBlock(array $settings): array
    {
        $attributes = static::parse($settings[BaseBlock::HTML_ATTRIBUTES] ?? null);
        $class = static::cleanClass($settings[BaseBlock::CSS_CLASS] ?? null);
        if ($class !== '') {
            $attributes['class'] = $class;
        }

        return $attributes;
    }

    /**
     * Renders attributes for a Blade view, escaped.
     *
     * @param array<string, string> $attributes
     */
    public static function toHtml(array $attributes): string
    {
        $html = [];
        foreach ($attributes as $name => $value) {
            $html[] = $value === '' && $name === 'hidden' ? e($name) : sprintf('%s="%s"', e($name), e($value));
        }

        return implode(' ', $html);
    }

    public static function cleanClass(mixed $class): string
    {
        if (!is_string($class)) {
            return '';
        }

        return trim((string)preg_replace('/\s+/', ' ', str_replace(['"', "'", '<', '>', '`'], '', $class)));
    }

    protected static function allowed(string $name, string $value): bool
    {
        if (!in_array($name, self::ALLOWED, true) && !str_starts_with($name, 'data-') && !str_starts_with($name, 'aria-')) {
            return false;
        }

        if (mb_strlen($value) > self::MAX_VALUE_LENGTH) {
            return false;
        }

        $compact = strtolower((string)preg_replace('/\s+/', '', $value));

        return !str_contains($compact, 'javascript:')
            && !str_contains($compact, 'vbscript:')
            && !str_contains($compact, 'expression(');
    }
}
