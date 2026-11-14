<?php

declare(strict_types=1);

namespace Igniter\Main\Classes;

use Igniter\System\Classes\ExtensionManager;
use InvalidArgumentException;

/**
 * Column layouts available to page builder sections.
 *
 * Equal layouts split the row into N equal columns. The other layouts are
 * expressed as spans on a 12 column grid. Themes map these to CSS classes.
 *
 * Extensions add layouts from registerSectionLayouts(), other packages with
 * SectionLayouts::register(), for example a two column layout with a narrow
 * left column:
 *
 *   SectionLayouts::register('sidebar-left-5', 'acme.extension::default.layout_5_7', [5, 7]);
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
        'sidebar-left-4' => ['label' => 'igniter::main.builder.layout_sidebar_left_4', 'spans' => [4, 8]],
        'sidebar-right-4' => ['label' => 'igniter::main.builder.layout_sidebar_right_4', 'spans' => [8, 4]],
        'sidebar-right-3' => ['label' => 'igniter::main.builder.layout_sidebar_right_3', 'spans' => [9, 3]],
        'sidebar-right-2' => ['label' => 'igniter::main.builder.layout_sidebar_right_2', 'spans' => [10, 2]],
        'center-3' => ['label' => 'igniter::main.builder.layout_center_3', 'spans' => [3, 6, 3]],
        'center-2' => ['label' => 'igniter::main.builder.layout_center_2', 'spans' => [2, 8, 2]],
    ];

    /** Minimum span (out of 12) for a column to accept "wide" blocks. */
    public const int WIDE_SPAN = 6;

    /** @var array<string, array{label: string, spans: list<int>}> Layouts registered by extensions and packages. */
    protected array $registered = [];

    protected bool $extensionLayoutsLoaded = false;

    /**
     * Adds a layout of columns with the given spans on a 12 column grid. The
     * spans must add up to 12. Themes lay out any spans, so no theme changes
     * are needed for a new layout.
     *
     * @param list<int> $spans
     */
    public static function register(string $code, string $label, array $spans): void
    {
        resolve(self::class)->registerLayout($code, $label, $spans);
    }

    /**
     * @param list<int> $spans
     */
    public function registerLayout(string $code, string $label, array $spans): void
    {
        if (array_key_exists($code, self::LAYOUTS)) {
            throw new InvalidArgumentException(sprintf('Section layout [%s] is built in and cannot be replaced.', $code));
        }

        $spans = array_values($spans);
        $valid = $spans !== [] && array_filter($spans, fn(mixed $span): bool => !is_int($span) || $span < 1 || $span > 12) === [];
        if (!$valid || array_sum($spans) !== 12) {
            throw new InvalidArgumentException(sprintf('Section layout [%s] needs whole column spans adding up to 12.', $code));
        }

        $this->registered[$code] = ['label' => $label, 'spans' => $spans];
    }

    /**
     * Every layout, the built in ones first.
     *
     * @return array<string, array{label: string, equal?: int, spans?: list<int>}>
     */
    public static function all(): array
    {
        return resolve(self::class)->layouts();
    }

    /**
     * @return array<string, array{label: string, equal?: int, spans?: list<int>}>
     */
    public function layouts(): array
    {
        $this->loadExtensionLayouts();

        return array_merge(self::LAYOUTS, $this->registered);
    }

    /**
     * Loads layouts extensions return from registerSectionLayouts():
     * ['code' => ['label' => 'lang.key', 'spans' => [4, 8]]]
     */
    protected function loadExtensionLayouts(): void
    {
        if ($this->extensionLayoutsLoaded) {
            return;
        }

        $this->extensionLayoutsLoaded = true;

        $registrations = resolve(ExtensionManager::class)->getRegistrationMethodValues('registerSectionLayouts');
        foreach ($registrations as $layouts) {
            foreach ((array)$layouts as $code => $layout) {
                $this->registerLayout((string)$code, (string)($layout['label'] ?? $code), (array)($layout['spans'] ?? []));
            }
        }
    }

    public static function has(string $code): bool
    {
        return array_key_exists($code, self::all());
    }

    /**
     * @return array<string, string> Layout codes mapped to translated labels.
     */
    public static function options(): array
    {
        return array_map(fn(array $layout): string => lang($layout['label']), self::all());
    }

    public static function isEqual(string $code): bool
    {
        return isset(self::all()[$code]['equal']);
    }

    public static function columnCount(string $code): int
    {
        $layout = self::find($code);

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
        $layout = self::find($code);

        if (isset($layout['spans'])) {
            return $layout['spans'];
        }

        $count = $layout['equal'];

        return array_fill(0, $count, 12 % $count === 0 ? intdiv(12, $count) : null);
    }

    /**
     * @return array{label: string, equal?: int, spans?: list<int>} The layout, or the default for unknown codes.
     */
    protected static function find(string $code): array
    {
        $layouts = self::all();

        return $layouts[$code] ?? $layouts[self::DEFAULT_LAYOUT];
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
            'html_attributes' => null,
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
            'html_attributes' => [
                'label' => 'igniter::main.builder.label_html_attributes',
                'type' => 'textarea',
                'comment' => 'igniter::main.builder.help_html_attributes',
                'attributes' => ['rows' => 3],
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
