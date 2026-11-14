<?php

declare(strict_types=1);

namespace Igniter\System\Classes\MailBuilder;

use Igniter\Flame\Exception\SystemException;
use Igniter\System\Classes\MailManager;
use Igniter\System\MailBlocks\Legacy;
use Igniter\System\Models\MailPartial;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Normalizes, validates and compiles mail builder documents.
 *
 * A document is a list of blocks: ['id' => 'b1', 'code' => 'heading', 'settings' => [...]].
 * Compiling turns it into the Blade stored in the template body, layout or
 * partial html, and its plain-text version. Every block is compiled with the
 * BladeWriter and the result is checked so it only contains Blade the writer
 * produces.
 */
class MailBuilder
{
    protected const string EXPRESSION = '(?:base64_decode\(\'[A-Za-z0-9+\/=]*\'\)|\$[A-Za-z_]\w*(?:\[\'\w+\'\])?)';

    public function __construct(protected MailBlockManager $blockManager) {}

    public function getBlockManager(): MailBlockManager
    {
        return $this->blockManager;
    }

    /**
     * Makes the compile context for a record.
     *
     * @param array<int, array{name: string, label?: string, sample?: string}> $params Partial parameters
     */
    public function makeContext(string $mode, ?string $code = null, array $params = [], bool $preview = false): CompileContext
    {
        throw_unless(in_array($mode, CompileContext::MODES, true), new SystemException(sprintf('Invalid mail builder mode [%s].', $mode)));

        $manager = resolve(MailManager::class);

        // Templates use the global variables and the groups they were registered with,
        // layouts and partials only the global variables
        $groups = $mode === CompileContext::MODE_TEMPLATE
            ? $manager->getTemplateVariableGroups($code)
            : [MailManager::GLOBAL_VARIABLES];

        return new CompileContext(
            mode: $mode,
            variables: new MailVariables((array)$manager->listRegisteredVariables(), $groups),
            code: $code,
            params: $params,
            partials: $this->listPartials($mode === CompileContext::MODE_PARTIAL ? $code : null),
            preview: $preview,
        );
    }

    /**
     * Partial codes and names that may be included, without the partial being edited.
     *
     * @return array<string, string>
     */
    public function listPartials(?string $exceptCode = null): array
    {
        $partials = [];
        foreach ((array)resolve(MailManager::class)->listRegisteredPartials() as $code => $view) {
            $partials[(string)$code] = (string)$code;
        }

        foreach (MailPartial::query()->get(['code', 'name', 'is_custom']) as $partial) {
            $partials[(string)$partial->code] = (string)($partial->name ?: $partial->code);
        }

        unset($partials[(string)$exceptCode]);
        ksort($partials);

        return $partials;
    }

    //
    // Normalizing and validation
    //

    /**
     * Cleans a posted document: drops malformed entries and blocks of another
     * mode, sanitizes settings and adds required blocks. Blocks whose code is
     * no longer registered are kept, so they come back when their extension is.
     */
    public function normalize(mixed $value, CompileContext $context): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $blocks = [];
        $ids = [];
        $unique = [];
        foreach (is_array($value) ? $value : [] as $item) {
            if (!is_array($item) || !is_string($item['code'] ?? null) || !preg_match('/^[\w.:-]+$/', $item['code'])) {
                continue;
            }

            $id = is_string($item['id'] ?? null) && preg_match('/^[A-Za-z0-9_-]{1,40}$/', $item['id']) && !isset($ids[$item['id']])
                ? $item['id']
                : $this->makeId();
            $ids[$id] = true;

            $settings = is_array($item['settings'] ?? null) ? $item['settings'] : [];

            if (!($block = $this->blockManager->findBlock($item['code'])) instanceof BaseMailBlock) {
                $blocks[] = ['id' => $id, 'code' => $item['code'], 'settings' => $settings, 'unavailable' => true];

                continue;
            }

            if (!$block->supportsMode($context->mode)) {
                continue;
            }

            if ($block->isUnique() && isset($unique[$block->getCode()])) {
                continue;
            }

            $unique[$block->getCode()] = true;
            $blocks[] = ['id' => $id, 'code' => $block->getCode(), 'settings' => $block->sanitize($settings)];
        }

        return $this->ensureRequiredBlocks($blocks, $context);
    }

    /**
     * Validates the block settings. Errors are keyed by `blocks.{id}.{field}`.
     *
     * @throws ValidationException
     */
    public function validate(array $blocks, CompileContext $context): void
    {
        $messages = [];
        foreach ($blocks as $item) {
            if (!empty($item['unavailable']) || !($block = $this->blockManager->findBlock((string)$item['code'])) instanceof BaseMailBlock) {
                continue;
            }

            $labels = [];
            foreach ($block->formFields() as $name => $field) {
                $labels[$name] = lang((string)($field['label'] ?? $name));
            }

            $validator = Validator::make((array)$item['settings'], $block->validationRules(), [], $labels);
            foreach ($validator->errors()->messages() as $field => $fieldMessages) {
                $messages['blocks.'.$item['id'].'.'.$field] = $fieldMessages;
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    //
    // Compiling
    //

    public function compileHtml(array $blocks, CompileContext $context): string
    {
        $writer = new BladeWriter($context);

        $lines = [];
        foreach ($blocks as $item) {
            $block = empty($item['unavailable']) ? $this->blockManager->findBlock((string)$item['code']) : null;
            if (!$block instanceof BaseMailBlock) {
                continue;
            }

            if ($block instanceof Legacy) {
                // Trusted content read from a registered mail view, see Legacy::resolveSegment()
                $blade = (string)$block->resolveSegment((array)$item['settings'], $context);
            } else {
                $settings = (array)$item['settings'];
                $blade = $this->applyCondition($block, $settings, $writer, $block->toBlade($settings, $writer, $context))->toBlade();
                $this->assertSafe($blade);
            }

            if (trim($blade) !== '') {
                $lines[] = $this->wrapBlock($blade, $block, (string)$item['id'], $context);
            }
        }

        $html = implode("\n", $lines);

        return $context->isMode(CompileContext::MODE_LAYOUT) ? $this->wrapLayout($html) : $html;
    }

    public function compileText(array $blocks, CompileContext $context): string
    {
        $writer = new TextWriter($context);

        $parts = [];
        foreach ($blocks as $item) {
            $block = empty($item['unavailable']) ? $this->blockManager->findBlock((string)$item['code']) : null;
            if (!$block instanceof BaseMailBlock) {
                continue;
            }

            if ($block instanceof Legacy) {
                // Template Markdown reads as plain text, layout and partial markup does not
                $blade = $context->isMode(CompileContext::MODE_TEMPLATE)
                    ? strip_tags((string)$block->resolveSegment((array)$item['settings'], $context))
                    : '';
            } else {
                $settings = (array)$item['settings'];
                $blade = $this->applyCondition($block, $settings, $writer, $block->toText($settings, $writer, $context))->toBlade();
                $this->assertSafe($blade);
            }

            if (trim($blade) !== '') {
                $parts[] = trim($blade);
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Compiles a subject line: text with allowed `{{ $variable }}` tokens.
     */
    public function compileSubject(string $subject, CompileContext $context): string
    {
        // Registered subjects may use `{variable}` placeholders, written as variable tokens
        $subject = (string)preg_replace_callback('/(?<![{$])\{([A-Za-z_]\w*)\}(?!\})/', fn(array $matches): string => $context->allowsVariable($matches[1])
            ? '{{ $'.$matches[1].' }}'
            : $matches[0], $subject);

        $blade = (new TextWriter($context))->text($subject)->toBlade();

        $this->assertSafe($blade);

        return $blade;
    }

    /**
     * Checks compiled Blade only contains the constructs the writer produces:
     * allowed variable echoes, @partial, @foreach and @if(!empty()). It fails
     * closed when anything else is found.
     *
     * @throws SystemException
     */
    public function assertSafe(string $blade): void
    {
        $expression = self::EXPRESSION.'(?: \. '.self::EXPRESSION.')*';
        $arguments = "'[A-Za-z_]\\w*' => ".$expression;

        $residue = preg_replace([
            '/\{\{ \$[A-Za-z_]\w*(?:\[\'\w+\'\])? \}\}/',
            '/\{!! \$(?:body|slot) !!\}/',
            '/\{!! \$[A-Za-z_]\w*\[\'\w+\'\] !!\}/',
            '/@partial\(\'[\w.:-]+\'(?:, \[(?:'.$arguments.')(?:, '.$arguments.')*\])?\)/',
            '/@endpartial/',
            '/@foreach\(\$[A-Za-z_]\w* as \$[A-Za-z_]\w*\)/',
            '/@endforeach/',
            '/@if\(!?empty\(\$[A-Za-z_]\w*\)\)/',
            '/@endif/',
        ], '', $blade);

        foreach (['{{', '{!!', '!!}', '@', '<?', '?>'] as $syntax) {
            throw_if(is_null($residue) || str_contains($residue, $syntax), new SystemException(sprintf(
                'Compiled mail content contains Blade syntax [%s] the mail builder does not produce.', $syntax,
            )));
        }
    }

    /**
     * Shows the block only when its condition variable is set, or empty.
     */
    protected function applyCondition(BaseMailBlock $block, array $settings, BladeWriter $writer, BladeFragment $fragment): BladeFragment
    {
        $variable = $settings[BaseMailBlock::VISIBLE_WHEN] ?? null;
        if (!$block->isConditional() || $fragment->isEmpty() || !is_string($variable)
            || !$writer->getContext()->allowsVariable($variable)
        ) {
            return $fragment;
        }

        return ($settings[BaseMailBlock::VISIBLE_MODE] ?? 'set') === 'empty'
            ? $writer->ifEmpty($variable, $fragment)
            : $writer->ifSet($variable, $fragment);
    }

    protected function wrapBlock(string $blade, BaseMailBlock $block, string $id, CompileContext $context): string
    {
        $attribute = $context->preview ? sprintf(' data-mb-block="%s"', $id) : '';

        if ($block instanceof Legacy) {
            // Legacy content is Markdown, keep it apart so the Markdown pass still applies
            return $context->preview && !$context->isMode(CompileContext::MODE_LAYOUT)
                ? sprintf("<div%s>\n\n%s\n\n</div>", $attribute, $blade)
                : "\n".$blade."\n";
        }

        if ($context->isMode(CompileContext::MODE_LAYOUT)) {
            return $block->isLayoutRow()
                ? sprintf('<tbody%s>%s</tbody>', $attribute, $blade)
                : sprintf('<tbody%s><tr><td class="content-cell">%s</td></tr></tbody>', $attribute, $blade);
        }

        return sprintf('<div class="mail-block"%s>%s</div>', $attribute, $blade);
    }

    /**
     * Wraps layout rows in the mail document.
     */
    protected function wrapLayout(string $rows): string
    {
        return implode("\n", [
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
            '<html xmlns="http://www.w3.org/1999/xhtml">',
            '<head>',
            '<meta name="viewport" content="width=device-width, initial-scale=1.0"/>',
            '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>',
            '</head>',
            '<body>',
            '<style type="text/css">{!! $custom_css !!}{!! $layout_css !!}</style>',
            '<table class="wrapper" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">',
            '<table class="content" width="100%" cellpadding="0" cellspacing="0">',
            $rows,
            '</table>',
            '</td></tr></table>',
            '</body>',
            '</html>',
        ]);
    }

    protected function ensureRequiredBlocks(array $blocks, CompileContext $context): array
    {
        foreach ($this->blockManager->listBlocks($context) as $block) {
            if (!$block->isRequired($context->mode)) {
                continue;
            }

            $codes = array_column($blocks, 'code');
            if (!in_array($block->getCode(), $codes, true)) {
                $blocks[] = ['id' => $this->makeId(), 'code' => $block->getCode(), 'settings' => $block->defaults()];
            }
        }

        return array_values($blocks);
    }

    public function makeId(): string
    {
        return 'b'.Str::lower(Str::random(10));
    }
}
