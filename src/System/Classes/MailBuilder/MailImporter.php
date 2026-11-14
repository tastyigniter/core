<?php

declare(strict_types=1);

namespace Igniter\System\Classes\MailBuilder;

/**
 * Turns existing mail Markdown and Blade into mail builder blocks.
 *
 * Content is split into chunks on blank lines, keeping @partial, @foreach and
 * @if blocks together. Each chunk becomes a block where it can: headings,
 * text, images, dividers, buttons, panels, promotions, subcopy and tables. What cannot be turned into a
 * block depends on where it came from:
 *  - from a registered mail view (developer code), it becomes a read-only
 *    Legacy block referencing the view segment;
 *  - from the database (written in the admin), it becomes escaped Text, so
 *    any Blade in it is shown as typed and never runs.
 */
class MailImporter
{
    protected const string VARIABLE_TOKEN = '/\{\{\s*\$([A-Za-z_]\w*)(?:\[\'(\w+)\'\])?\s*\}\}/';

    /** @var list<string> */
    protected array $notices = [];

    public function __construct(protected MailBuilder $builder) {}

    /**
     * Imports content into a list of blocks.
     *
     * @param string|null $view The registered mail view the content was read from, when it was
     * @return array{blocks: list<array>, notices: list<string>}
     */
    public function import(string $source, CompileContext $context, ?string $view = null): array
    {
        $this->notices = [];

        $blocks = $context->isMode(CompileContext::MODE_LAYOUT)
            ? $this->importLayout($source, $view)
            : $this->importChunks($source, $context, $view);

        return [
            'blocks' => $this->builder->normalize($blocks, $context),
            'notices' => array_values(array_unique($this->notices)),
        ];
    }

    /**
     * Splits content into chunks on blank lines, keeping Blade blocks together.
     * Template content is Markdown, where an HTML block also ends at a blank line.
     *
     * @return list<string>
     */
    public static function splitChunks(string $source, bool $markdown = true): array
    {
        $source = str_replace(["\r\n", "\r"], "\n", $source);
        if (!$markdown) {
            return trim($source) === '' ? [] : [trim($source)];
        }

        $chunks = [];
        $current = [];
        $depth = 0;
        foreach (explode("\n", $source) as $line) {
            $depth += preg_match_all('/@(partial|foreach|forelse|if|isset|unless|empty)\b/', $line);
            $depth -= preg_match_all('/@(endpartial|endforeach|endforelse|endif|endisset|endunless|endempty)\b/', $line);
            $depth = max(0, $depth);

            if (trim($line) === '' && $depth === 0) {
                if ($current !== []) {
                    $chunks[] = trim(implode("\n", $current));
                    $current = [];
                }

                continue;
            }

            $current[] = $line;
        }

        if ($current !== []) {
            $chunks[] = trim(implode("\n", $current));
        }

        return array_values(array_filter($chunks, fn(string $chunk): bool => $chunk !== ''));
    }

    protected function importChunks(string $source, CompileContext $context, ?string $view): array
    {
        $blocks = [];
        $isMarkdown = $context->isMode(CompileContext::MODE_TEMPLATE);
        foreach (self::splitChunks($source, $isMarkdown) as $index => $chunk) {
            $imported = $this->importChunk($chunk, $context);
            $imported ??= [$view
                ? $this->legacyBlock($view, $index, $chunk)
                : $this->textBlock($chunk, $isMarkdown)];

            array_push($blocks, ...$imported);
        }

        return $blocks;
    }

    /**
     * Imports a chunk into one or more blocks, or null when it cannot be imported.
     *
     * @return list<array>|null
     */
    protected function importChunk(string $chunk, CompileContext $context): ?array
    {
        $chunk = $this->normalizeChunk($chunk, $context);

        if (!is_null($blocks = $this->importCondition($chunk, $context))) {
            return $blocks;
        }

        // Blocks registered by extensions recognise their own markup first
        foreach ($this->builder->getBlockManager()->listBlocks($context) as $block) {
            $settings = $block->importChunk($chunk, $context);
            if (!is_null($settings)) {
                return [$this->block($block->getCode(), $settings)];
            }
        }

        $block = $this->importCoreChunk($chunk, $context);

        return $block ? [$block] : null;
    }

    /**
     * Converts markup the blocks support in another form: line breaks become
     * Markdown line breaks and `{variable}` placeholders become variable tokens.
     */
    protected function normalizeChunk(string $chunk, CompileContext $context): string
    {
        $chunk = trim((string)preg_replace('/[ \t]*<br\s*\/?>[ \t]*\n?/i', "  \n", $chunk));

        return (string)preg_replace_callback('/(?<![{$])\{([A-Za-z_]\w*)\}(?!\})/', fn(array $matches): string => $context->allowsVariable($matches[1])
            ? '{{ $'.$matches[1].' }}'
            : $matches[0], $chunk);
    }

    /**
     * Imports a chunk wrapped in a condition, such as `@if(!empty($x))` or
     * `@isset($x)`, as blocks shown only when the variable is set or empty.
     *
     * @return list<array>|null
     */
    protected function importCondition(string $chunk, CompileContext $context): ?array
    {
        if (!preg_match('/^@(if|isset|unless)\s*\((.+?)\)[ \t]*\n(.*?)(?:\n[ \t]*@else[ \t]*\n(.*?))?\n[ \t]*@end(?:if|isset|unless)$/s', $chunk, $matches)) {
            return null;
        }

        $condition = $this->parseCondition($matches[1], trim($matches[2]));
        if (is_null($condition) || !$context->allowsVariable($condition[0])) {
            return null;
        }

        [$variable, $mode] = $condition;
        $branches = [[$matches[3], $mode]];
        if (($matches[4] ?? '') !== '') {
            $branches[] = [$matches[4], $mode === 'set' ? 'empty' : 'set'];
        }

        $blocks = [];
        foreach ($branches as [$content, $branchMode]) {
            foreach (self::splitChunks($this->dedent($content)) as $innerChunk) {
                $imported = $this->importChunk($innerChunk, $context);
                if (is_null($imported)) {
                    return null;
                }

                foreach ($imported as $block) {
                    // Nested conditions cannot be expressed with one condition per block
                    if (!empty($block['settings'][BaseMailBlock::VISIBLE_WHEN])) {
                        return null;
                    }

                    $block['settings'][BaseMailBlock::VISIBLE_WHEN] = $variable;
                    $block['settings'][BaseMailBlock::VISIBLE_MODE] = $branchMode;
                    $blocks[] = $block;
                }
            }
        }

        return $blocks;
    }

    /**
     * @return array{0: string, 1: string}|null The variable and when the content is shown
     */
    protected function parseCondition(string $directive, string $expression): ?array
    {
        $patterns = [
            '/^\$([A-Za-z_]\w*)$/' => 'set',
            '/^!\s*\$([A-Za-z_]\w*)$/' => 'empty',
            '/^!\s*empty\(\s*\$([A-Za-z_]\w*)\s*\)$/' => 'set',
            '/^empty\(\s*\$([A-Za-z_]\w*)\s*\)$/' => 'empty',
            '/^isset\(\s*\$([A-Za-z_]\w*)\s*\)$/' => 'set',
            '/^!\s*isset\(\s*\$([A-Za-z_]\w*)\s*\)$/' => 'empty',
        ];

        foreach ($patterns as $pattern => $mode) {
            if (preg_match($pattern, $expression, $matches)) {
                if ($directive === 'unless') {
                    $mode = $mode === 'set' ? 'empty' : 'set';
                }

                return [$matches[1], $mode];
            }
        }

        return null;
    }

    protected function dedent(string $content): string
    {
        $lines = explode("\n", $content);
        $indent = null;
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $indent = min($indent ?? PHP_INT_MAX, strlen($line) - strlen(ltrim($line, " \t")));
            }
        }

        return implode("\n", array_map(fn(string $line): string => substr($line, (int)$indent), $lines));
    }

    protected function importCoreChunk(string $chunk, CompileContext $context): ?array
    {
        if ($context->isMode(CompileContext::MODE_PARTIAL) && preg_match('/^\{(?:\{|!!)\s*\$slot\s*(?:\}|!!)\}$/', $chunk)) {
            return $this->block('slot');
        }

        if (!$context->isMode(CompileContext::MODE_TEMPLATE)) {
            return null;
        }

        if (preg_match('/^(#{1,3})\s+([^\n]+)$/', $chunk, $matches) && $this->isPlainText($matches[2], $context)) {
            return $this->block('heading', ['text' => trim($matches[2]), 'level' => 'h'.strlen($matches[1])]);
        }

        if (preg_match('/^(?:-{3,}|\*{3,}|_{3,}|<hr\s*\/?>)$/', $chunk)) {
            return $this->block('divider');
        }

        if (preg_match('/^!\[([^\]\n]*)\]\(([^)\s]+)\)$/', $chunk, $matches) && $this->isPlainText($matches[0], $context)) {
            return $this->block('image', ['src' => $matches[2], 'alt' => $matches[1]]);
        }

        if (preg_match('/^@partial\(\s*\'([\w.:-]+)\'\s*(?:,\s*\[(.*?)\])?\s*\)(.*)@endpartial$/s', $chunk, $matches)) {
            return $this->importPartial($matches[1], $matches[2], trim($matches[3]), $context);
        }

        if ($this->isPlainText($chunk, $context)) {
            return $this->block('text', ['content' => $chunk]);
        }

        return null;
    }

    protected function importPartial(string $code, string $arguments, string $slot, CompileContext $context): ?array
    {
        $params = $this->parseArguments($arguments);
        if (is_null($params) || !$this->isPlainText($slot, $context)) {
            return null;
        }

        if ($code === 'button' && isset($params['url'])) {
            return $this->block('button', array_filter([
                'label' => $slot,
                'url' => $params['url'],
                'style' => $params['type'] ?? $params['color'] ?? 'primary',
                'align' => $params['align'] ?? null,
            ], fn(?string $value): bool => !is_null($value)));
        }

        if ($params !== []) {
            return null;
        }

        return match ($code) {
            'panel', 'promotion', 'subcopy' => $this->block($code, ['content' => $this->dedent($slot)]),
            'table' => $this->importTable($this->dedent($slot)),
            default => null,
        };
    }

    /**
     * Imports a Markdown table, such as `| Item | Price |`, as a table block.
     */
    protected function importTable(string $source): ?array
    {
        $lines = array_values(array_filter(array_map(trim(...), explode("\n", $source)), fn(string $line): bool => $line !== ''));
        if (count($lines) < 2 || !preg_match('/^\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)*\|?$/', $lines[1])) {
            return null;
        }

        $columns = [];
        $aligns = $this->tableCells($lines[1]);
        foreach ($this->tableCells($lines[0]) as $index => $label) {
            $align = $aligns[$index] ?? '';
            $columns[] = [
                'label' => $label,
                'align' => match (true) {
                    str_starts_with($align, ':') && str_ends_with($align, ':') => 'center',
                    str_ends_with($align, ':') => 'right',
                    default => 'left',
                },
            ];
        }

        $rows = [];
        foreach (array_slice($lines, 2) as $line) {
            if (!str_contains($line, '|')) {
                return null;
            }

            $rows[] = $this->tableCells($line);
        }

        return $this->block('table', ['table' => BaseMailBlock::sanitizeTable(['columns' => $columns, 'rows' => $rows])]);
    }

    /**
     * @return list<string>
     */
    protected function tableCells(string $line): array
    {
        $line = trim($line);
        $line = str_starts_with($line, '|') ? substr($line, 1) : $line;
        $line = str_ends_with($line, '|') ? substr($line, 0, -1) : $line;

        return array_map(trim(...), explode('|', $line));
    }

    /**
     * Parses simple partial arguments: 'key' => 'text' or 'key' => $variable.
     *
     * @return array<string, string>|null Null when an argument is not simple
     */
    protected function parseArguments(string $arguments): ?array
    {
        $params = [];
        $arguments = trim($arguments);
        if ($arguments === '') {
            return $params;
        }

        $pattern = '/\s*[\'"](\w+)[\'"]\s*=>\s*(?:\'([^\'\\\\]*)\'|"([^"\\\\$]*)"|\$([A-Za-z_]\w*))\s*(?:,|$)/A';
        $offset = 0;
        while ($offset < strlen($arguments)) {
            if (!preg_match($pattern, $arguments, $matches, 0, $offset)) {
                return null;
            }

            $params[$matches[1]] = match (true) {
                ($matches[4] ?? '') !== '' => '{{ $'.$matches[4].' }}',
                ($matches[3] ?? '') !== '' => $matches[3],
                default => $matches[2],
            };
            $offset += strlen($matches[0]);
        }

        return $params;
    }

    /**
     * Layouts are rebuilt from blocks: header, content slot and footer.
     */
    protected function importLayout(string $source, ?string $view = null): array
    {
        $blocks = [];
        if (preg_match('/@partial\(\s*\'header\'.*?\)(.*?)@endpartial/s', $source, $matches)) {
            $blocks[] = $this->block('header', [
                'show_logo' => str_contains($matches[1], 'logo'),
                'text' => '{{ $site_name }}',
            ]);
        }

        $blocks[] = $this->block('content_slot');

        if (preg_match('/@partial\(\s*\'footer\'.*?\)(.*?)@endpartial/s', $source, $matches)) {
            $footer = strip_tags(str_replace("{{ date('Y') }}", date('Y'), $matches[1]));
            $blocks[] = $this->block('footer', ['content' => trim(html_entity_decode($footer, ENT_QUOTES, 'UTF-8'))]);
        }

        // Registered layouts are the core structure, customized ones may have lost markup
        if (is_null($view)) {
            $this->notices[] = lang('igniter::system.mail_builder.notice_layout_rebuilt');
        }

        return $blocks;
    }

    protected function legacyBlock(string $view, int $index, string $chunk): array
    {
        $this->notices[] = lang('igniter::system.mail_builder.notice_legacy_blocks');

        return $this->block('legacy', ['view' => $view, 'segment' => $index, 'hash' => sha1($chunk)]);
    }

    protected function textBlock(string $chunk, bool $isMarkdown): array
    {
        $this->notices[] = lang('igniter::system.mail_builder.notice_escaped_content');

        return $this->block('text', ['content' => $isMarkdown ? $chunk : strip_tags($chunk)]);
    }

    /**
     * Whether the content has no Blade other than allowed variable tokens and no HTML.
     */
    protected function isPlainText(string $content, CompileContext $context): bool
    {
        $content = (string)preg_replace_callback(self::VARIABLE_TOKEN, function(array $matches) use ($context): string {
            $field = $matches[2] ?? '';

            return ($field === '' && $context->allowsVariable($matches[1])) ? '' : $matches[0];
        }, $content);

        return !preg_match('/\{\{|\{!!|(?<![\w.])@[a-z]|<\?|<[a-z!\/]/i', $content);
    }

    protected function block(string $code, array $settings = []): array
    {
        return ['id' => $this->builder->makeId(), 'code' => $code, 'settings' => $settings];
    }
}
