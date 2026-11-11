<?php

declare(strict_types=1);

namespace Igniter\Main\Classes;

/**
 * Base class for page builder blocks.
 *
 * Themes define their own blocks by extending this class (or ComponentBlock)
 * and registering them with the BlockManager.
 */
abstract class BaseBlock
{
    /**
     * Returns the block details:
     * code, name, description, icon, unique, wide, pages.
     */
    abstract public function blockDetails(): array;

    /**
     * Form field definitions used to edit the block settings in the admin.
     */
    public function defineFields(): array
    {
        return [];
    }

    /**
     * Default settings for a newly added block.
     */
    public function defaults(): array
    {
        $defaults = [];
        foreach ($this->defineFields() as $name => $field) {
            $defaults[$name] = $field['default'] ?? null;
        }

        return $defaults;
    }

    /**
     * Returns JSON-safe data passed to the block view or Inertia page.
     */
    public function resolve(array $settings): array
    {
        return $settings;
    }

    /**
     * Cleans the settings before they are saved or rendered.
     * Only keys known to the block are kept.
     */
    public function sanitize(array $settings): array
    {
        return array_intersect_key($settings, $this->defineFields() + $this->defaults());
    }

    /**
     * Short text shown on the block card in the admin editor. Defaults to the
     * first non-empty text setting.
     */
    public function summary(array $settings): string
    {
        foreach ($settings as $value) {
            if (is_scalar($value) && !is_bool($value) && trim(strip_tags((string)$value)) !== '') {
                return str_limit(trim(strip_tags((string)$value)), 60);
            }
        }

        return '';
    }

    /**
     * Validation rules for the block settings, taken from the `validationRule`
     * key of each field definition.
     */
    public function validationRules(): array
    {
        $rules = [];
        foreach ($this->defineFields() as $name => $field) {
            if (!empty($field['validationRule'])) {
                $rules[$name] = $field['validationRule'];
            }
        }

        return $rules;
    }

    /**
     * Renders the block for Blade themes using the theme `_builder.blocks.{code}` view.
     */
    public function render(string $viewNamespace, array $block): string
    {
        return view($viewNamespace.'::_builder.blocks.'.$this->getCode(), [
            'block' => $block,
            'settings' => $block['settings'] ?? [],
            'data' => $block['data'] ?? [],
        ])->render();
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
            'unique' => false,
            'wide' => false,
            'pages' => null,
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

    public function isUnique(): bool
    {
        return (bool)$this->getDetails()['unique'];
    }

    public function isWide(): bool
    {
        return (bool)$this->getDetails()['wide'];
    }

    /**
     * Whether the block may be used on the given page. Blocks without a `pages`
     * restriction are available everywhere.
     */
    public function isAllowedOnPage(?string $pageCode): bool
    {
        $pages = $this->getDetails()['pages'];

        if (empty($pages)) {
            return true;
        }

        return in_array($pageCode, (array)$pages, true);
    }
}
