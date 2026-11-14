<?php

declare(strict_types=1);

namespace Igniter\System\Classes\MailBuilder;

/**
 * The mail variables registered with the MailManager, parsed into scalars,
 * arrays and array item fields.
 *
 * Registered keys look like `{{ $order_number }}`, `$order_menus[]` and
 * `{{ $order_menu['menu_name'] }}`. Item fields belong to the array registered
 * before them in the same group.
 */
class MailVariables
{
    /** @var array<string, string> Scalar variable names and labels. */
    protected array $scalars = [];

    /** @var array<string, array{label: string, item: ?string, fields: array<string, array{label: string, raw: bool}>}> */
    protected array $arrays = [];

    /** @var list<array{code: string, label: string, variables: list<array>}> */
    protected array $groups = [];

    /**
     * @param array<string, array> $registered Variable definitions keyed by group
     * @param list<string>|null $groups Only these groups, all groups when null
     */
    public function __construct(array $registered = [], ?array $groups = null)
    {
        foreach ($registered as $groupLabel => $definitions) {
            if (is_null($groups) || in_array((string)$groupLabel, $groups, true)) {
                $this->addGroup((string)$groupLabel, (array)$definitions);
            }
        }
    }

    public function isScalar(string $name): bool
    {
        return array_key_exists($name, $this->scalars);
    }

    public function isArray(string $name): bool
    {
        return isset($this->arrays[$name]['item']);
    }

    public function itemName(string $array): ?string
    {
        return $this->arrays[$array]['item'] ?? null;
    }

    public function hasItemField(string $array, string $field): bool
    {
        return isset($this->arrays[$array]['fields'][$field]);
    }

    public function isRawItemField(string $array, string $field): bool
    {
        return (bool)($this->arrays[$array]['fields'][$field]['raw'] ?? false);
    }

    /**
     * @return array<string, array{label: string, raw: bool}>
     */
    public function itemFields(string $array): array
    {
        return $this->arrays[$array]['fields'] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function scalars(): array
    {
        return $this->scalars;
    }

    /**
     * @return array<string, array{label: string, item: ?string, fields: array}>
     */
    public function arrays(): array
    {
        return array_filter($this->arrays, fn(array $array): bool => !is_null($array['item']));
    }

    /**
     * Grouped variables for the picker.
     */
    public function groups(): array
    {
        return $this->groups;
    }

    protected function addGroup(string $groupLabel, array $definitions): void
    {
        $group = [
            'key' => $groupLabel,
            'code' => str_slug(class_basename(str_replace(['::', '.'], '\\', $groupLabel))),
            'label' => lang($groupLabel),
            'variables' => [],
        ];

        $currentArray = null;
        foreach ($definitions as $token => $label) {
            $token = (string)$token;
            $label = lang((string)$label);

            if (preg_match('/^\{\{\s*\$([A-Za-z_]\w*)\s*\}\}$/', $token, $matches)) {
                $this->scalars[$matches[1]] = $label;
                $group['variables'][] = ['type' => 'scalar', 'name' => $matches[1], 'token' => '{{ $'.$matches[1].' }}', 'label' => $label];

                continue;
            }

            if (preg_match('/^\$([A-Za-z_]\w*)\[\]$/', $token, $matches)) {
                $currentArray = $matches[1];
                $this->arrays[$currentArray] ??= ['label' => $label, 'item' => null, 'fields' => []];
                $group['variables'][] = ['type' => 'array', 'name' => $currentArray, 'token' => null, 'label' => $label];

                continue;
            }

            if ($currentArray && preg_match('/^\{(\{|!!)\s*\$([A-Za-z_]\w*)\[\'(\w+)\'\]\s*(?:\}|!!)\}$/', $token, $matches)) {
                [, $open, $item, $field] = $matches;
                $this->arrays[$currentArray]['item'] ??= $item;
                if ($this->arrays[$currentArray]['item'] !== $item) {
                    continue;
                }

                $raw = $open === '!!';
                $this->arrays[$currentArray]['fields'][$field] = ['label' => $label, 'raw' => $raw];
                $group['variables'][] = [
                    'type' => 'item',
                    'name' => $field,
                    'array' => $currentArray,
                    'item' => $item,
                    'token' => sprintf("{{ \$%s['%s'] }}", $item, $field),
                    'label' => $label,
                ];
            }
        }

        $this->groups[] = $group;
    }
}
