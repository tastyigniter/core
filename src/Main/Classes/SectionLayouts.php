<?php

declare(strict_types=1);

namespace Igniter\Main\Classes;

/**
 * Column layouts available to page builder sections.
 *
 * Equal layouts split the row into N equal columns. The other layouts are
 * expressed as spans on a 12 column grid. Themes map these to CSS classes.
 */
class SectionLayouts
{
    public const string DEFAULT_LAYOUT = 'cols-1';

    /** @var array<string, array{label: string, equal?: int, spans?: list<int>}> */
    protected const array LAYOUTS = [
        'cols-1' => ['label' => 'igniter::main.builder.layout_cols_1', 'equal' => 1],
        'cols-2' => ['label' => 'igniter::main.builder.layout_cols_2', 'equal' => 2],
        'cols-3' => ['label' => 'igniter::main.builder.layout_cols_3', 'equal' => 3],
        'cols-4' => ['label' => 'igniter::main.builder.layout_cols_4', 'equal' => 4],
        'cols-5' => ['label' => 'igniter::main.builder.layout_cols_5', 'equal' => 5],
        'cols-6' => ['label' => 'igniter::main.builder.layout_cols_6', 'equal' => 6],
        'sidebar-left-3' => ['label' => 'igniter::main.builder.layout_sidebar_left_3', 'spans' => [3, 9]],
        'sidebar-left-2' => ['label' => 'igniter::main.builder.layout_sidebar_left_2', 'spans' => [2, 10]],
        'sidebar-right-4' => ['label' => 'igniter::main.builder.layout_sidebar_right_4', 'spans' => [8, 4]],
        'sidebar-right-3' => ['label' => 'igniter::main.builder.layout_sidebar_right_3', 'spans' => [9, 3]],
        'sidebar-right-2' => ['label' => 'igniter::main.builder.layout_sidebar_right_2', 'spans' => [10, 2]],
        'center-3' => ['label' => 'igniter::main.builder.layout_center_3', 'spans' => [3, 6, 3]],
        'center-2' => ['label' => 'igniter::main.builder.layout_center_2', 'spans' => [2, 8, 2]],
    ];

    /** Minimum span (out of 12) for a column to accept "wide" blocks. */
    public const int WIDE_SPAN = 6;

    public static function has(string $code): bool
    {
        return array_key_exists($code, self::LAYOUTS);
    }

    /**
     * @return array<string, string> Layout codes mapped to translated labels.
     */
    public static function options(): array
    {
        return array_map(fn(array $layout): string => lang($layout['label']), self::LAYOUTS);
    }

    public static function isEqual(string $code): bool
    {
        return isset(self::LAYOUTS[$code]['equal']);
    }

    public static function columnCount(string $code): int
    {
        $layout = self::LAYOUTS[$code] ?? self::LAYOUTS[self::DEFAULT_LAYOUT];

        return $layout['equal'] ?? count($layout['spans']);
    }

    /**
     * Returns the 12 column grid span of each column. Equal layouts that do not
     * divide 12 evenly (5 columns) return null spans, themes should use an equal
     * width grid for those.
     *
     * @return list<int|null>
     */
    public static function spans(string $code): array
    {
        $layout = self::LAYOUTS[$code] ?? self::LAYOUTS[self::DEFAULT_LAYOUT];

        if (isset($layout['spans'])) {
            return $layout['spans'];
        }

        $count = $layout['equal'];

        return array_fill(0, $count, 12 % $count === 0 ? intdiv(12, $count) : null);
    }

    /**
     * Whether a column is wide enough for blocks that require a wide column.
     */
    public static function isWideColumn(string $code, int $columnIndex): bool
    {
        $span = self::spans($code)[$columnIndex] ?? null;

        return $span !== null && $span >= self::WIDE_SPAN;
    }

    /**
     * @return array{container: string, spacing: string, background_color: ?string, background_image: ?string, anchor: ?string, css_class: ?string}
     */
    public static function defaultSettings(): array
    {
        return [
            'container' => 'boxed',
            'spacing' => 'md',
            'background_color' => null,
            'background_image' => null,
            'anchor' => null,
            'css_class' => null,
        ];
    }

    /**
     * Form field definitions for section settings.
     */
    public static function settingsFields(): array
    {
        return [
            'container' => [
                'label' => 'igniter::main.builder.label_container',
                'type' => 'radiotoggle',
                'default' => 'boxed',
                'options' => [
                    'boxed' => 'igniter::main.builder.text_boxed',
                    'full' => 'igniter::main.builder.text_full_width',
                ],
            ],
            'spacing' => [
                'label' => 'igniter::main.builder.label_spacing',
                'type' => 'radiotoggle',
                'default' => 'md',
                'options' => [
                    'none' => 'igniter::main.builder.text_none',
                    'sm' => 'igniter::main.builder.text_small',
                    'md' => 'igniter::main.builder.text_medium',
                    'lg' => 'igniter::main.builder.text_large',
                ],
            ],
            'background_color' => [
                'label' => 'igniter::main.builder.label_background_color',
                'type' => 'colorpicker',
            ],
            'background_image' => [
                'label' => 'igniter::main.builder.label_background_image',
                'type' => 'mediafinder',
            ],
            'anchor' => [
                'label' => 'igniter::main.builder.label_anchor',
                'type' => 'text',
                'comment' => 'igniter::main.builder.help_anchor',
            ],
            'css_class' => [
                'label' => 'igniter::main.builder.label_css_class',
                'type' => 'text',
            ],
        ];
    }

    /**
     * Keeps only known setting keys and fills in defaults.
     */
    public static function normalizeSettings(mixed $settings): array
    {
        $settings = is_array($settings) ? $settings : [];
        $defaults = self::defaultSettings();
        $settings = array_merge($defaults, array_intersect_key($settings, $defaults));

        if (!in_array($settings['container'], ['boxed', 'full'], true)) {
            $settings['container'] = $defaults['container'];
        }

        if (!in_array($settings['spacing'], ['none', 'sm', 'md', 'lg'], true)) {
            $settings['spacing'] = $defaults['spacing'];
        }

        return $settings;
    }
}
