<?php

namespace Igniter\Admin\Classes;

use Igniter\Flame\Traits\EventEmitter;
use Igniter\System\Classes\BaseExtension;
use Igniter\System\Classes\ExtensionManager;
use Igniter\System\Traits\ViewMaker;
use Igniter\User\Facades\AdminAuth;

class Navigation
{
    use EventEmitter;
    use ViewMaker;

    protected array $navItems = [];

    protected array $mainItems = [];

    protected bool $navItemsLoaded = false;

    protected ?string $navContextItemCode = null;

    protected ?string $navContextParentCode = null;

    protected array $callbacks = [];

    protected ?string $previousPageUrl = null;

    protected static array $navItemDefaults = [
        'code' => null,
        'class' => null,
        'href' => null,
        'icon' => null,
        'title' => null,
        'child' => null,
        'priority' => 500,
        'permission' => null,
    ];

    public function __construct(?string $path = null)
    {
        $this->viewPath[] = $path;
    }

    public function setContext(string $itemCode, ?string $parentCode = null)
    {
        $this->navContextItemCode = $itemCode;
        $this->navContextParentCode = is_null($parentCode) ? $itemCode : $parentCode;
    }

    public function getNavItems(): array
    {
        if (!$this->navItems) {
            $this->loadItems();
        }

        return $this->navItems;
    }

    public function getVisibleNavItems(): array
    {
        $navItems = $this->getNavItems();

        uasort($navItems, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        $navItems = $this->filterPermittedNavItems($navItems);

        foreach ($navItems as &$navItem) {
            if (!isset($navItem['child']) || !count($navItem['child'])) {
                continue;
            }

            uasort($navItem['child'], function($a, $b) {
                return $a['priority'] - $b['priority'];
            });

            $navItem['child'] = $this->filterPermittedNavItems($navItem['child']);
        }

        return $navItems;
    }

    public function isActiveNavItem(string $code): bool
    {
        if ($code == $this->navContextParentCode) {
            return true;
        }

        if ($code == $this->navContextItemCode) {
            return true;
        }

        return false;
    }

    public function getMainItems(): array
    {
        if (!$this->mainItems) {
            $this->loadItems();
        }

        return $this->filterPermittedNavItems($this->mainItems);
    }

    public function render(string $partial): string
    {
        $navItems = $this->getVisibleNavItems();

        return $this->makePartial($partial, [
            'navItems' => $navItems,
        ]);
    }

    public function addNavItem(string $itemCode, array $options = [], ?string $parentCode = null)
    {
        $navItem = array_merge(self::$navItemDefaults, $options);
        $navItem['code'] = $itemCode;

        if ($parentCode) {
            if (!isset($this->navItems[$parentCode])) {
                $this->navItems[$parentCode] = array_merge(self::$navItemDefaults, [
                    'code' => $parentCode,
                    'class' => $parentCode,
                ]);
            }

            $this->navItems[$parentCode]['child'][$itemCode] = $navItem;
        } else {
            $this->navItems[$itemCode] = $navItem;
        }
    }

    public function mergeNavItem(string $itemCode, array $options = [], ?string $parentCode = null)
    {
        if ($parentCode) {
            if ($oldItem = array_get($this->navItems, $parentCode.'.child.'.$itemCode, [])) {
                $this->navItems[$parentCode]['child'][$itemCode] = array_merge($oldItem, $options);
            }
        } else {
            if ($oldItem = array_get($this->navItems, $itemCode, [])) {
                $this->navItems[$itemCode] = array_merge($oldItem, $options);
            }
        }
    }

    public function removeNavItem(string $itemCode, ?string $parentCode = null)
    {
        if (!is_null($parentCode)) {
            unset($this->navItems[$parentCode]['child'][$itemCode]);
        } else {
            unset($this->navItems[$itemCode]);
        }
    }

    public function removeMainItem(string $itemCode)
    {
        unset($this->mainItems[$itemCode]);
    }

    public function loadItems()
    {
        if ($this->navItemsLoaded) {
            return;
        }

        // Load app items
        foreach ($this->callbacks as $callback) {
            $callback($this);
        }

        // Load extension items
        $extensions = resolve(ExtensionManager::class)->getExtensions();
        foreach ($extensions as $extension) {
            if (!$extension instanceof BaseExtension) {
                continue;
            }

            $items = $extension->registerNavigation();

            $this->registerNavItems($items);
        }

        $this->fireSystemEvent('admin.navigation.extendItems');

        $this->navItemsLoaded = true;
    }

    public function filterPermittedNavItems(array $items): array
    {
        return collect($items)->filter(function($item) {
            if (!$permission = array_get($item, 'permission')) {
                return true;
            }

            return AdminAuth::user()->hasPermission($permission);
        })->toArray();
    }

    public function setPreviousUrl(string $pathOrUrl): static
    {
        $url = starts_with($pathOrUrl, ['http://', 'https://'])
            ? $pathOrUrl : admin_url($pathOrUrl);

        $previousUrl = url()->previous();
        if (str_contains($previousUrl, '?') && rtrim(preg_replace('/\?.*/', '', $previousUrl), '/') === rtrim($url, '/')) {
            $url = $previousUrl;
        }

        $this->previousPageUrl = $url;

        return $this;
    }

    public function getPreviousUrl(): ?string
    {
        return $this->previousPageUrl;
    }

    //
    // Registration
    //

    public function registerMainItems(?array $definitions = null)
    {
        if (!$this->mainItems) {
            $this->mainItems = [];
        }

        foreach ($definitions as $name => $definition) {
            if ($definition instanceof MainMenuItem) {
                $name = $definition->itemName;
            }

            $this->mainItems[$name] = $definition;
        }
    }

    public function registerNavItems(?array $definitions = null, ?string $parent = null)
    {
        if (!$this->navItems) {
            $this->navItems = [];
        }

        foreach ($definitions as $name => $definition) {
            if (isset($definition['child']) && count($definition['child'])) {
                $this->registerNavItems($definition['child'], $name);
            }

            if (array_except($definition, 'child')) {
                $this->addNavItem($name, $definition, $parent);
            }
        }
    }

    public function registerNavItem(string $code, array $item, ?string $parent = null)
    {
        $item = array_filter(array_merge(self::$navItemDefaults, $item));

        if (!is_null($parent)) {
            $this->navItems[$parent]['child'][$code] = $item;
        } else {
            $this->navItems[$code] = $item;
        }
    }

    /**
     * Registers a callback function that defines navigation items.
     * The callback function should register permissions by calling the manager's
     * registerNavItems() function. The manager instance is passed to the
     * callback function as an argument. Usage:
     * <pre>
     *   Template::registerCallback(function($manager){
     *       $manager->registerNavItems([...]);
     *   });
     * </pre>
     *
     * @param callable $callback A callable function.
     */
    public function registerCallback(callable $callback)
    {
        $this->callbacks[] = $callback;
    }
}
