<?php

namespace Igniter\Admin\Widgets;

use Exception;
use Igniter\Admin\Classes\BaseMainMenuWidget;
use Igniter\Admin\Classes\BaseWidget;
use Igniter\Admin\Classes\MainMenuItem;
use Igniter\Flame\Exception\FlashException;

class Menu extends BaseWidget
{
    /**
     * @var ?array Item definition configuration.
     */
    public ?array $items = null;

    /**
     * @var null|string|array The context of this menu, items that do not belong
     * to this context will not be shown.
     */
    public null|string|array $context = null;

    protected $defaultAlias = 'top-menu';

    /**
     * @var bool Determines if item definitions have been created.
     */
    protected bool $itemsDefined = false;

    /**
     * @var array Collection of all items used in this menu.
     */
    protected array $allItems = [];

    protected array $widgets = [];

    /**
     * @var array List of CSS classes to apply to the menu container element
     */
    public array $cssClasses = [];

    public function initialize()
    {
        $this->fillFromConfig([
            'items',
            'context',
        ]);
    }

    public function bindToController()
    {
        $this->defineMenuItems();
        parent::bindToController();
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('menu/top_menu');
    }

    protected function prepareVars()
    {
        $this->defineMenuItems();
        $this->vars['cssClasses'] = implode(' ', $this->cssClasses);
        $this->vars['items'] = $this->getItems();
    }

    public function loadAssets()
    {
        $this->addJs('mainmenu.js', 'mainmenu-js');
        $this->addJs('formwidgets/recordeditor.modal.js', 'recordeditor-modal-js');
    }

    /**
     * Renders the HTML element for a item
     *
     * @return string
     */
    public function renderItemElement($item)
    {
        $params = ['item' => $item];

        return $this->makePartial('menu/item_'.$item->type, $params);
    }

    /**
     * Creates a flat array of menu items from the configuration.
     */
    protected function defineMenuItems()
    {
        if ($this->itemsDefined) {
            return;
        }

        if (!isset($this->items) || !is_array($this->items)) {
            $this->items = [];
        }

        $this->addItems($this->items);

        $this->allItems = collect($this->allItems)->sortBy('priority')->all();

        // Bind all main menu widgets to controller
        foreach ($this->allItems as $item) {
            if ($item->type !== 'widget') {
                continue;
            }

            $widget = $this->makeMenuItemWidget($item);
            $widget->bindToController();
        }

        $this->itemsDefined = true;
    }

    /**
     * Programatically add items, used internally and for extensibility.
     */
    public function addItems(array $items)
    {
        foreach ($items as $name => $config) {
            $itemObj = $this->makeMenuItem($name, $config);

            // Check that the menu item matches the active context
            if ($itemObj->context !== null) {
                $context = (is_array($itemObj->context)) ? $itemObj->context : [$itemObj->context];
                if (!in_array($this->getContext(), $context)) {
                    continue;
                }
            }

            $this->allItems[$itemObj->itemName] = $itemObj;
        }
    }

    /**
     * Creates a menu item object from name and configuration.
     *
     * @return \Igniter\Admin\Classes\MainMenuItem
     */
    protected function makeMenuItem($name, $config)
    {
        if ($config instanceof MainMenuItem) {
            return $config;
        }

        $label = $config['label'] ?? null;
        $itemType = $config['type'] ?? null;

        $item = new MainMenuItem($name, $label);
        $item->displayAs($itemType, $config);


        // Get menu item options from model
        $optionModelTypes = ['dropdown', 'partial'];
        if (in_array($item->type, $optionModelTypes, false)) {
            // Defer the execution of option data collection
            $item->options(function () use ($item, $config) {
                $itemOptions = $config['options'] ?? null;

                return $this->getOptionsFromModel($item, $itemOptions);
            });
        }

        return $item;
    }

    /**
     * Get all the registered items for the instance.
     * @return array
     */
    public function getItems()
    {
        return $this->allItems;
    }

    /**
     * Get a specified item object
     *
     * @param string $item
     *
     * @return mixed
     * @throws \Exception
     */
    public function getItem($item)
    {
        if (!isset($this->allItems[$item])) {
            throw FlashException::error(sprintf(lang('igniter::admin.side_menu.alert_no_definition'), $item));
        }

        return $this->allItems[$item];
    }

    public function getLoggedUser()
    {
        if (!$this->getController()->checkUser()) {
            return false;
        }

        return $this->getController()->getUser();
    }

    public function makeMenuItemWidget(MainMenuItem $item): ?BaseMainMenuWidget
    {
        if ($item->type !== 'widget') {
            return null;
        }

        if (isset($this->widgets[$item->itemName])) {
            return $this->widgets[$item->itemName];
        }

        $widgetConfig = $this->makeConfig($item->config);
        $widgetConfig['alias'] = $this->alias.studly_case(name_to_id($item->itemName));

        throw_unless(class_exists($widgetClass = $widgetConfig['widget']), new Exception(sprintf(
            lang('igniter::admin.alert_widget_class_name'), $widgetClass
        )));

        return $this->widgets[$item->itemName] = new $widgetClass($this->controller, $item, $widgetConfig);
    }

    //
    // Event handlers
    //

    /**
     * Update a menu item value.
     * @return array
     * @throws \Exception
     */
    public function onGetDropdownOptions()
    {
        if (!strlen($itemName = input('item'))) {
            throw FlashException::error(lang('igniter::admin.side_menu.alert_invalid_menu'));
        }

        if (!$item = $this->getItem($itemName)) {
            throw FlashException::error(sprintf(lang('igniter::admin.side_menu.alert_menu_not_found'), $itemName));
        }

        // Return a partial if item has a path defined
        return [
            '#'.$this->getId($item->itemName.'-options') => $this->makePartial($item->path, [
                'item' => $item,
                'itemOptions' => $item->options(),
            ]),
        ];
    }

    /**
     * Returns the active context for displaying the menu.
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    protected function getOptionsFromModel($item, $itemOptions)
    {
        if (is_array($itemOptions) && is_callable($itemOptions)) {
            $user = $this->getLoggedUser();
            $itemOptions = $itemOptions($this, $item, $user);
        }

        return $itemOptions;
    }
}
