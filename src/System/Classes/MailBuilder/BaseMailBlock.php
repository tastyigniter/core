<?php

declare(strict_types=1);

namespace Igniter\System\Classes\MailBuilder;

/**
 * Base class for mail builder blocks.
 *
 * Blocks never return raw strings: they build their output with the
 * BladeWriter, which escapes every value. Extensions register blocks from
 * BaseExtension::registerMailBlocks() or MailBlockManager::registerCallback().
 *
 * Field types: text, textarea, markdown, select, number, switch, color, url,
 * image, variable, param, table.
 */
abstract class BaseMailBlock
{
    /** @var array<string, array> Fields added through MailBlockManager::extendBlock(). */
    protected array $extraFields = [];

    /** Most columns a table field keeps. */
    public const int TABLE_MAX_COLUMNS = 8;

    /** Most rows a table field keeps. */
    public const int TABLE_MAX_ROWS = 100;

    /** Setting every conditional block has: the variable that decides whether it is shown. */
    public const string VISIBLE_WHEN = 'visible_when';

    /** Setting every conditional block has: shown when the variable is set or empty. */
    public const string VISIBLE_MODE = 'visible_mode';

    /**
     * Returns the block details: code, name, description, icon, category,
     * modes (template, layout, partial), unique, required.
     */
    abstract public function blockDetails(): array;

    /**
     * Compiles the block to HTML mail Blade.
     */
    abstract public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment;

    /**
     * Compiles the block to plain-text mail Blade. Blocks without a text
     * version leave it out of the plain-text mail.
     */
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        return $writer->none();
    }

    /**
     * Form field definitions used to edit the block settings in the builder.
     */
    public function defineFields(): array
    {
        return [];
    }

    /**
     * Whether the block is offered for the record being edited. Override to
     * limit a block to some templates, for example by template code.
     */
    public function appliesTo(CompileContext $context): bool
    {
        return true;
    }

    /**
     * Extra sample data used when previewing the block.
     */
    public function preview(array $settings): array
    {
        return [];
    }

    /**
     * Script files the builder loads for custom field types used by the block.
     *
     * @return list<string>
     */
    public function assets(): array
    {
        return [];
    }

    //
    // Fields and settings
    //

    public function formFields(): array
    {
        return array_merge($this->defineFields(), $this->extraFields, $this->isConditional() ? static::conditionFields() : []);
    }

    /**
     * The fields every conditional block has: show the block only when a
     * variable has a value, or only when it is empty.
     */
    public static function conditionFields(): array
    {
        return [
            self::VISIBLE_WHEN => [
                'label' => 'igniter::system.mail_builder.label_visible_when',
                'type' => 'variable',
                'comment' => 'igniter::system.mail_builder.help_visible_when',
                'span' => 'left',
                'validationRule' => ['nullable', 'string', 'regex:/^[A-Za-z_]\w*$/'],
            ],
            self::VISIBLE_MODE => [
                'label' => 'igniter::system.mail_builder.label_visible_mode',
                'type' => 'select',
                'default' => 'set',
                'span' => 'right',
                'options' => [
                    'set' => 'igniter::system.mail_builder.text_visible_set',
                    'empty' => 'igniter::system.mail_builder.text_visible_empty',
                ],
                'validationRule' => ['required', 'in:set,empty'],
            ],
        ];
    }

    /**
     * Whether the block can be shown depending on a variable. Structural
     * blocks, such as the content slot, are always shown.
     */
    public function isConditional(): bool
    {
        return (bool)$this->getDetails()['conditional'];
    }

    /**
     * Turns a chunk of a registered mail view into block settings, used when
     * importing existing content. Returns null when the chunk is not this block.
     */
    public function importChunk(string $chunk, CompileContext $context): ?array
    {
        return null;
    }

    public function addFields(array $fields): void
    {
        $this->extraFields = array_merge($this->extraFields, $fields);
    }

    public function removeField(string $name): void
    {
        unset($this->extraFields[$name]);
    }

    public function defaults(): array
    {
        $defaults = [];
        foreach ($this->formFields() as $name => $field) {
            $defaults[$name] = $field['default'] ?? null;
        }

        return $defaults;
    }

    /**
     * Validation rules for the block settings, taken from the `validationRule`
     * key of each field definition.
     */
    public function validationRules(): array
    {
        $rules = [];
        foreach ($this->formFields() as $name => $field) {
            if (!empty($field['validationRule'])) {
                $rules[$name] = $field['validationRule'];
            }
        }

        return $rules;
    }

    /**
     * Cleans the settings before they are saved or compiled. Only keys known
     * to the block are kept and values are cast for their field type.
     */
    public function sanitize(array $settings): array
    {
        $result = [];
        foreach ($this->formFields() as $name => $field) {
            $value = array_key_exists($name, $settings) ? $settings[$name] : ($field['default'] ?? null);
            $result[$name] = match ($field['type'] ?? 'text') {
                'switch' => (bool)$value,
                'number' => is_numeric($value) ? (int)$value : ($field['default'] ?? null),
                'table' => static::sanitizeTable($value),
                default => is_scalar($value) ? (string)$value : null,
            };
        }

        return $result;
    }

    /**
     * The field definitions for the builder, with translated labels.
     */
    public function fieldSchema(): array
    {
        $schema = [];
        foreach ($this->formFields() as $name => $field) {
            $options = $field['options'] ?? null;
            if (is_array($options)) {
                $options = array_map(fn($label) => lang((string)$label), $options);
            }

            $schema[] = array_filter([
                'name' => $name,
                'label' => lang((string)($field['label'] ?? $name)),
                'type' => $field['type'] ?? 'text',
                'comment' => isset($field['comment']) ? lang((string)$field['comment']) : null,
                'placeholder' => isset($field['placeholder']) ? lang((string)$field['placeholder']) : null,
                'options' => $options,
                'span' => $field['span'] ?? null,
                'min' => $field['min'] ?? null,
                'max' => $field['max'] ?? null,
                'variables' => $field['variables'] ?? in_array($field['type'] ?? 'text', ['text', 'textarea', 'markdown', 'url', 'table'], true),
            ], fn($value): bool => !is_null($value));
        }

        return $schema;
    }

    /**
     * Short text shown on the block in the structure list.
     */
    public function summary(array $settings): string
    {
        // The condition settings describe when the block shows, not what it shows
        foreach (array_diff_key($settings, static::conditionFields()) as $value) {
            if (is_string($value) && trim(strip_tags($value)) !== '') {
                return str_limit(trim(strip_tags($value)), 60);
            }
        }

        return '';
    }

    //
    // Details
    //

    public function getDetails(): array
    {
        return array_merge([
            'code' => null,
            'name' => null,
            'description' => null,
            'icon' => 'fa fa-cube',
            'category' => 'content',
            'modes' => CompileContext::MODES,
            'unique' => false,
            'required' => false,
            'conditional' => true,
        ], $this->blockDetails());
    }

    public function getCode(): string
    {
        return (string)$this->getDetails()['code'];
    }

    public function getName(): string
    {
        return lang((string)($this->getDetails()['name'] ?? $this->getCode()));
    }

    /**
     * @return list<string>
     */
    public function getModes(): array
    {
        return array_values((array)$this->getDetails()['modes']);
    }

    public function supportsMode(string $mode): bool
    {
        return in_array($mode, $this->getModes(), true);
    }

    public function isUnique(): bool
    {
        return (bool)$this->getDetails()['unique'];
    }

    /**
     * Whether a document of the mode must contain the block.
     */
    public function isRequired(string $mode): bool
    {
        $required = $this->getDetails()['required'];

        return is_array($required) ? in_array($mode, $required, true) : (bool)$required;
    }

    /**
     * Whether the block is shown read-only in the builder.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * Whether the block outputs its own layout table rows.
     */
    public function isLayoutRow(): bool
    {
        return (bool)($this->getDetails()['row'] ?? false);
    }

    /**
     * The block definition for the builder palette.
     */
    public function toConfig(): array
    {
        $details = $this->getDetails();

        return [
            'code' => $this->getCode(),
            'name' => $this->getName(),
            'description' => $details['description'] ? lang((string)$details['description']) : null,
            'icon' => $details['icon'],
            'category' => $details['category'],
            'unique' => $this->isUnique(),
            'readOnly' => $this->isReadOnly(),
            'fields' => $this->fieldSchema(),
            'defaults' => $this->defaults(),
        ];
    }

    /**
     * Cleans a table field value: a list of columns, each with a label and an
     * alignment, and a list of rows, each with one text cell per column.
     *
     * @return array{columns: list<array{label: string, align: string}>, rows: list<list<string>>}
     */
    public static function sanitizeTable(mixed $value): array
    {
        $value = is_array($value) ? $value : [];

        $columns = [];
        foreach (array_slice(array_values((array)($value['columns'] ?? [])), 0, self::TABLE_MAX_COLUMNS) as $column) {
            $column = is_array($column) ? $column : ['label' => $column];
            $columns[] = [
                'label' => is_scalar($column['label'] ?? null) ? mb_substr((string)$column['label'], 0, 255) : '',
                'align' => in_array($column['align'] ?? null, ['left', 'center', 'right'], true) ? $column['align'] : 'left',
            ];
        }

        $rows = [];
        foreach (array_slice(array_values((array)($value['rows'] ?? [])), 0, self::TABLE_MAX_ROWS) as $row) {
            $row = array_values((array)$row);
            $cells = [];
            foreach (array_keys($columns) as $index) {
                $cells[] = is_scalar($row[$index] ?? null) ? mb_substr((string)$row[$index], 0, 1000) : '';
            }

            $rows[] = $cells;
        }

        return ['columns' => $columns, 'rows' => $rows];
    }
}
