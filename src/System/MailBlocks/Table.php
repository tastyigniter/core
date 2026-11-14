<?php

declare(strict_types=1);

namespace Igniter\System\MailBlocks;

use Igniter\System\Classes\MailBuilder\BaseMailBlock;
use Igniter\System\Classes\MailBuilder\BladeFragment;
use Igniter\System\Classes\MailBuilder\BladeWriter;
use Igniter\System\Classes\MailBuilder\CompileContext;
use Igniter\System\Classes\MailBuilder\TextWriter;
use Override;

/**
 * A table of text cells with a header row, wrapped in the table partial.
 * Cells may contain variables.
 */
class Table extends PartialBlock
{
    public function blockDetails(): array
    {
        return [
            'code' => 'table',
            'name' => 'igniter::system.mail_builder.block_table',
            'description' => 'igniter::system.mail_builder.block_table_description',
            'icon' => 'fa fa-table',
        ];
    }

    public function partialCode(): string
    {
        return 'table';
    }

    #[Override]
    public function defineFields(): array
    {
        return [
            'table' => [
                'label' => 'igniter::system.mail_builder.label_table',
                'type' => 'table',
                'default' => [
                    'columns' => [['label' => 'Item', 'align' => 'left'], ['label' => 'Price', 'align' => 'right']],
                    'rows' => [['', '']],
                ],
            ],
        ];
    }

    #[Override]
    public function toBlade(array $settings, BladeWriter $writer, CompileContext $context): BladeFragment
    {
        $table = BaseMailBlock::sanitizeTable($settings['table'] ?? []);
        if ($table['columns'] === []) {
            return $writer->none();
        }

        $cell = fn(string $tag, string $align, string $value): BladeFragment => $writer->tag($tag, [
            'align' => $align,
            'style' => $writer->static('text-align: '.$align),
        ], $writer->text($value));

        $header = [];
        foreach ($table['columns'] as $column) {
            $header[] = $cell('th', $column['align'], $column['label']);
        }

        $rows = [];
        foreach ($table['rows'] as $row) {
            $cells = [];
            foreach ($table['columns'] as $index => $column) {
                $cells[] = $cell('td', $column['align'], $row[$index]);
            }

            $rows[] = $writer->tag('tr', [], $writer->join(...$cells));
        }

        return $this->wrap($writer->tag('table', ['width' => '100%', 'cellpadding' => '0', 'cellspacing' => '0'], $writer->join(
            $writer->tag('thead', [], $writer->tag('tr', [], $writer->join(...$header))),
            $writer->tag('tbody', [], $writer->join(...$rows)),
        )), $writer, $context);
    }

    #[Override]
    public function toText(array $settings, TextWriter $writer, CompileContext $context): BladeFragment
    {
        $table = BaseMailBlock::sanitizeTable($settings['table'] ?? []);
        if ($table['columns'] === []) {
            return $writer->none();
        }

        // Empty cells are written as a dash so the columns stay in line
        $cell = fn(string $value): BladeFragment => trim($value) === '' ? $writer->static('-') : $writer->text($value);

        $lines = [$writer->joinWith(' | ', ...array_map(fn(array $column): BladeFragment => $cell($column['label']), $table['columns']))];
        foreach ($table['rows'] as $row) {
            $lines[] = $writer->joinWith(' | ', ...array_map($cell, $row));
        }

        return $this->wrap($writer->joinWith("\n", ...$lines), $writer, $context);
    }

    #[Override]
    public function summary(array $settings): string
    {
        $table = BaseMailBlock::sanitizeTable($settings['table'] ?? []);

        return str_limit(implode(', ', array_filter(array_column($table['columns'], 'label'))), 60);
    }
}
