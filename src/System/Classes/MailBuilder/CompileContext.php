<?php

declare(strict_types=1);

namespace Igniter\System\Classes\MailBuilder;

/**
 * What a mail builder document is being compiled for: the mode, the record
 * code, the variables that may be used and whether it is a preview.
 */
class CompileContext
{
    public const string MODE_TEMPLATE = 'template';

    public const string MODE_LAYOUT = 'layout';

    public const string MODE_PARTIAL = 'partial';

    public const array MODES = [self::MODE_TEMPLATE, self::MODE_LAYOUT, self::MODE_PARTIAL];

    /** @var list<array{array: string, item: string}> The loops being compiled, innermost last. */
    protected array $loops = [];

    /**
     * @param array<int, array{name: string, label?: string, sample?: string}> $params Declared partial parameters
     * @param array<string, string> $partials Partial codes and names that may be included
     */
    public function __construct(
        public readonly string $mode,
        public readonly MailVariables $variables,
        public readonly ?string $code = null,
        public readonly array $params = [],
        public readonly array $partials = [],
        public readonly bool $preview = false,
    ) {}

    public function isMode(string $mode): bool
    {
        return $this->mode === $mode;
    }

    /**
     * Whether a scalar variable may be echoed.
     */
    public function allowsVariable(string $name): bool
    {
        if ($this->variables->isScalar($name)) {
            return true;
        }

        return $this->isMode(self::MODE_PARTIAL) && in_array($name, $this->paramNames(), true);
    }

    /**
     * Whether an item field may be echoed: the item must belong to a loop being compiled.
     */
    public function allowsItemField(string $item, string $field): bool
    {
        foreach ($this->loops as $loop) {
            if ($loop['item'] === $item) {
                return $this->variables->hasItemField($loop['array'], $field);
            }
        }

        return false;
    }

    public function isRawItemField(string $item, string $field): bool
    {
        foreach ($this->loops as $loop) {
            if ($loop['item'] === $item) {
                return $this->variables->isRawItemField($loop['array'], $field);
            }
        }

        return false;
    }

    public function allowsPartial(string $code): bool
    {
        return array_key_exists($code, $this->partials);
    }

    /**
     * @return list<string>
     */
    public function paramNames(): array
    {
        return array_values(array_filter(array_map(fn(array $param) => (string)($param['name'] ?? ''), $this->params)));
    }

    public function enterLoop(string $array, string $item): void
    {
        $this->loops[] = ['array' => $array, 'item' => $item];
    }

    public function leaveLoop(): void
    {
        array_pop($this->loops);
    }

    public function withPreview(bool $preview = true): self
    {
        return new self($this->mode, $this->variables, $this->code, $this->params, $this->partials, $preview);
    }
}
