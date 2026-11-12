<?php

declare(strict_types=1);

namespace Igniter\Main\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\System\Classes\ComponentManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Component as BladeComponent;
use Livewire\Component as LivewireComponent;
use Livewire\Livewire;
use Override;

/**
 * A block that wraps a configurable Livewire or Blade component.
 *
 * The block settings form is built from the component's defineProperties(),
 * and the component is rendered with the block settings as its properties.
 */
class ComponentBlock extends BaseBlock
{
    protected ?array $propertyConfig = null;

    protected ?array $propertyDefaults = null;

    final public function __construct(protected string $componentCode, protected array $details = []) {}

    public static function make(string $componentCode, array $details = []): static
    {
        return new static($componentCode, $details);
    }

    public function getComponentCode(): string
    {
        return $this->componentCode;
    }

    #[Override]
    public function blockDetails(): array
    {
        $meta = resolve(ComponentManager::class)->findComponent($this->componentCode) ?? [];

        return array_merge([
            'code' => str_after($this->componentCode, '::'),
            'name' => $meta['name'] ?? $this->componentCode,
            'description' => $meta['description'] ?? null,
        ], $this->details);
    }

    #[Override]
    public function defineFields(): array
    {
        if (is_null($this->propertyConfig)) {
            $this->loadPropertyConfig();
        }

        return $this->propertyConfig;
    }

    #[Override]
    public function defaults(): array
    {
        if (is_null($this->propertyDefaults)) {
            $this->loadPropertyConfig();
        }

        return $this->propertyDefaults;
    }

    #[Override]
    public function render(string $viewNamespace, array $block): string
    {
        // The CSS class and HTML attributes go on the element around the component.
        $settings = static::withoutAttributes($block['settings'] ?? []);
        $manager = resolve(ComponentManager::class);
        $className = $manager->resolve($this->componentCode);

        if (is_subclass_of($className, LivewireComponent::class)) {
            return $this->renderLivewire((string)($block['id'] ?? ''), $settings);
        }

        if (is_subclass_of($className, BladeComponent::class)) {
            return $this->renderBlade($settings);
        }

        throw new SystemException(sprintf('Component "%s" is not a Livewire or Blade component.', $this->componentCode));
    }

    /**
     * Livewire components look up their configured properties by "{code} {alias}",
     * so each block is given its own alias and its settings registered under it.
     * Properties the page configures for the component, including ones that are
     * not editable block settings, are kept underneath the block settings.
     */
    protected function renderLivewire(string $blockId, array $settings): string
    {
        $alias = preg_replace('/[^A-Za-z0-9_]/', '', $blockId) ?: 'block';
        $settings = array_merge(controller()->getConfiguredComponent($this->componentCode), $settings);

        controller()->getPage()?->setConfigurableComponentProperties($this->componentCode.' '.$alias, $settings);

        return Livewire::mount($this->componentCode, array_merge($settings, ['alias' => $alias]));
    }

    /**
     * Blade components take explicitly passed attributes over configured properties.
     */
    protected function renderBlade(array $settings): string
    {
        $bindings = [];
        foreach (array_keys($settings) as $name) {
            if (preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', (string)$name)) {
                $bindings[] = sprintf(':%s="$__settings[\'%s\']"', snake_case((string)$name, '-'), $name);
            }
        }

        return Blade::render(
            sprintf('<x-%s %s />', $this->componentCode, implode(' ', $bindings)),
            ['__settings' => $settings],
            deleteCachedView: false,
        );
    }

    protected function loadPropertyConfig(): void
    {
        $manager = resolve(ComponentManager::class);
        $component = $manager->makeComponent($this->componentCode);

        $this->propertyConfig = $manager->getComponentPropertyConfig($component, false);
        $this->propertyDefaults = array_except($manager->getComponentPropertyValues($component), ['alias']);
    }
}
