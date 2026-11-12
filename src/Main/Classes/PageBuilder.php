<?php

declare(strict_types=1);

namespace Igniter\Main\Classes;

use Igniter\Main\Models\ThemePageLayout;

/**
 * Loads, normalizes, resolves and renders page builder sections.
 *
 * Section structure:
 * <pre>
 *   [
 *     'id' => 's_xxxxxxxx',
 *     'layout' => 'sidebar-left-3',
 *     'settings' => ['container' => 'boxed', ...],
 *     'columns' => [
 *       ['blocks' => [['id' => 'b_xxxxxxxx', 'code' => 'rich-text', 'settings' => [...]]]],
 *       ['blocks' => []],
 *     ],
 *   ]
 * </pre>
 */
class PageBuilder
{
    public function __construct(protected BlockManager $blockManager) {}

    /**
     * Returns the normalized sections for a theme page: the saved layout when one
     * exists, otherwise the theme default. Missing required blocks are appended.
     */
    public function forPage(Theme $theme, string $pageCode): array
    {
        $saved = ThemePageLayout::findFor($theme->getName(), $pageCode)?->sections;

        $sections = $this->normalize($theme, $saved ?? $this->defaultSections($theme, $pageCode), $pageCode);

        return $this->ensureRequiredBlocks($theme, $sections, $pageCode);
    }

    /**
     * Returns the theme default sections for a page, normalized.
     */
    public function defaultSections(Theme $theme, string $pageCode): array
    {
        return $this->normalize($theme, $theme->getBuildablePage($pageCode)['default'] ?? [], $pageCode);
    }

    /**
     * Returns the codes of blocks that must be present on a page.
     *
     * @return list<string>
     */
    public function requiredBlocks(Theme $theme, ?string $pageCode): array
    {
        return $pageCode ? ($theme->getBuildablePage($pageCode)['required'] ?? []) : [];
    }

    /**
     * Appends a single column section holding any required blocks missing from
     * the normalized sections.
     */
    public function ensureRequiredBlocks(Theme $theme, array $sections, ?string $pageCode): array
    {
        return $this->appendRequiredBlocks($theme, $sections, $this->requiredBlocks($theme, $pageCode), $pageCode);
    }

    /**
     * Validates and cleans the sections structure. Unknown layouts fall back to a
     * single column, columns are fitted to the layout, unknown or disallowed
     * blocks are dropped, unique blocks are kept once and settings are sanitized.
     */
    public function normalize(Theme $theme, mixed $sections, ?string $pageCode = null): array
    {
        $blocks = $this->blockManager->listBlocks($theme, $pageCode);
        $usedUniqueBlocks = [];
        $result = [];

        foreach (is_array($sections) ? $sections : [] as $section) {
            if (!is_array($section)) {
                continue;
            }

            $layout = is_string($section['layout'] ?? null) && SectionLayouts::has($section['layout'])
                ? $section['layout']
                : SectionLayouts::DEFAULT_LAYOUT;

            $columns = [];
            foreach ($this->fitColumns($section['columns'] ?? [], SectionLayouts::columnCount($layout)) as $index => $columnBlocks) {
                $normalizedBlocks = [];
                foreach ($columnBlocks as $block) {
                    $definition = is_array($block) && is_string($block['code'] ?? null) ? ($blocks[$block['code']] ?? null) : null;
                    if (!$definition) {
                        continue;
                    }

                    if ($definition->isWide() && !SectionLayouts::isWideColumn($layout, $index)) {
                        continue;
                    }

                    if ($definition->isUnique()) {
                        if (isset($usedUniqueBlocks[$definition->getCode()])) {
                            continue;
                        }

                        $usedUniqueBlocks[$definition->getCode()] = true;
                    }

                    $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];

                    $normalizedBlocks[] = [
                        'id' => $this->normalizeId($block['id'] ?? null, 'b_'),
                        'code' => $definition->getCode(),
                        'settings' => $definition->sanitize(array_merge($definition->defaults(), $settings)),
                    ];
                }

                $columns[] = ['blocks' => $normalizedBlocks];
            }

            $result[] = [
                'id' => $this->normalizeId($section['id'] ?? null, 's_'),
                'layout' => $layout,
                'settings' => SectionLayouts::normalizeSettings($section['settings'] ?? []),
                'columns' => $columns,
            ];
        }

        return $result;
    }

    /**
     * Adds layout information and resolved block data to normalized sections,
     * ready to be passed to a view or serialized for Inertia.
     */
    public function resolve(Theme $theme, array $sections, ?string $pageCode = null): array
    {
        $blocks = $this->blockManager->listBlocks($theme, $pageCode);

        return array_map(function(array $section) use ($blocks): array {
            $spans = SectionLayouts::spans($section['layout']);

            $section['equal'] = SectionLayouts::isEqual($section['layout']);
            $section['attributes'] = HtmlAttributes::parse($section['settings']['html_attributes'] ?? null);
            $section['columns'] = array_map(function(array $column, int $index) use ($blocks, $spans): array {
                $column['span'] = $spans[$index] ?? null;
                $column['blocks'] = array_map(function(array $block) use ($blocks): array {
                    $block['data'] = $blocks[$block['code']]->resolve(BaseBlock::withoutAttributes($block['settings']));
                    $block['attributes'] = HtmlAttributes::forBlock($block['settings']);

                    return $block;
                }, $column['blocks']);

                return $column;
            }, $section['columns'], array_keys($section['columns']));

            return $section;
        }, $sections);
    }

    /**
     * Renders normalized sections with the theme `_builder.section` view.
     */
    public function renderBlade(Theme $theme, array $sections, ?string $pageCode = null): string
    {
        $viewNamespace = $this->getViewNamespace($theme);
        $blocks = $this->blockManager->listBlocks($theme, $pageCode);

        $html = '';
        foreach ($this->resolve($theme, $sections, $pageCode) as $section) {
            $html .= view($viewNamespace.'::_builder.section', [
                'section' => $section,
                'renderBlock' => fn(array $block): string => $blocks[$block['code']]->render($viewNamespace, $block),
            ])->render();
        }

        return $html;
    }

    /**
     * Renders a theme page sections. Used by the `@themeBlocks` directive.
     */
    public function renderPage(?Theme $theme, ?string $pageCode): string
    {
        if (!$theme instanceof Theme || !$pageCode) {
            return '';
        }

        return $this->renderBlade($theme, $this->forPage($theme, $pageCode), $pageCode);
    }

    /**
     * Child theme views are registered on the parent theme namespace, so blocks
     * are always rendered from the root theme namespace.
     */
    public function getViewNamespace(Theme $theme): string
    {
        return $theme->hasParent() ? (string)$theme->getParentName() : $theme->getName();
    }

    protected function appendRequiredBlocks(Theme $theme, array $sections, array $required, ?string $pageCode): array
    {
        $present = [];
        foreach ($sections as $section) {
            foreach ($section['columns'] as $column) {
                foreach ($column['blocks'] as $block) {
                    $present[$block['code']] = true;
                }
            }
        }

        $missing = [];
        foreach ($required as $code) {
            if (!isset($present[$code]) && $definition = $this->blockManager->findBlock($theme, $code, $pageCode)) {
                $missing[] = ['code' => $definition->getCode()];
            }
        }

        if ($missing === []) {
            return $sections;
        }

        $sections[] = ['layout' => SectionLayouts::DEFAULT_LAYOUT, 'columns' => [['blocks' => $missing]]];

        return $this->normalize($theme, $sections, $pageCode);
    }

    /**
     * Fits the given columns to the layout column count. Blocks from extra
     * columns are moved into the last column.
     *
     * @return list<list<mixed>>
     */
    protected function fitColumns(mixed $columns, int $count): array
    {
        $columnBlocks = [];
        foreach (is_array($columns) ? array_values($columns) : [] as $column) {
            $columnBlocks[] = is_array($column) && is_array($column['blocks'] ?? null) ? array_values($column['blocks']) : [];
        }

        $fitted = array_slice($columnBlocks, 0, $count);
        foreach (array_slice($columnBlocks, $count) as $extraBlocks) {
            $fitted[$count - 1] = array_merge($fitted[$count - 1], $extraBlocks);
        }

        return array_pad($fitted, $count, []);
    }

    protected function normalizeId(mixed $id, string $prefix): string
    {
        if (is_string($id) && preg_match('/^[A-Za-z0-9_-]{1,40}$/', $id)) {
            return $id;
        }

        return $prefix.strtolower(str_random(8));
    }
}
