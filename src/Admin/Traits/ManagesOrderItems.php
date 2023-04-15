<?php

namespace Igniter\Admin\Traits;

use Igniter\Admin\Models\Menu;
use Igniter\Admin\Models\MenuItemOptionValue;
use Illuminate\Support\Facades\DB;

trait ManagesOrderItems
{
    /**
     * Subtract cart item quantity from menu stock quantity
     *
     * @return void
     */
    public function subtractStock()
    {
        $orderMenuOptions = $this->getOrderMenuOptions();
        $this->getOrderMenus()->each(function ($orderMenu) use ($orderMenuOptions) {
            if (!$menu = Menu::find($orderMenu->menu_id)) {
                return true;
            }

            optional($menu->getStockByLocation($this->location))
                ->updateStockSold($this->getKey(), $orderMenu->quantity);

            $orderMenuOptions
                ->where('order_menu_id', $orderMenu->order_menu_id)
                ->each(function ($orderMenuOption) {
                    if (!$menuItemOptionValue = MenuItemOptionValue::find(
                        $orderMenuOption->menu_option_value_id
                    )) {
                    return true;
                    }

                    if (!$menuOptionValue = $menuItemOptionValue->option_value) {
                        return true;
                    }

                    optional($menuOptionValue->getStockByLocation($this->location))
                        ->updateStockSold($this->getKey(), $orderMenuOption->quantity);
                });
        });
    }

    /**
     * Return all order menu by order_id
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMenus()
    {
        return $this->menus;
    }

    /**
     * Return all order menu options by order_id
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMenuOptions()
    {
        return $this->menu_options->groupBy('order_menu_id');
    }

    /**
     * Return all order menus merged with order menu options
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderMenusWithOptions()
    {
        $this->load('menus.menu_options');

        return $this->menus;
    }

    /**
     * Return all order totals by order_id
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOrderTotals()
    {
        return $this->totals->sortBy('priority');
    }

    /**
     * Add cart menu items to order by order_id
     */
    public function addOrderMenus(array $content)
    {
        $orderId = $this->getKey();
        if (!is_numeric($orderId)) {
            return false;
        }

        $this->menus()->delete();
        $this->menu_options()->delete();

        foreach ($content as $rowId => $cartItem) {
            if ($rowId != $cartItem->rowId) {
            continue;
            }

            $orderMenu = $this->menus()->create([
                'menu_id' => $cartItem->id,
                'name' => $cartItem->name,
                'quantity' => $cartItem->qty,
                'price' => $cartItem->price,
                'subtotal' => $cartItem->subtotal,
                'comment' => $cartItem->comment,
                'option_values' => serialize($cartItem->options),
            ]);

            if ($orderMenu && count($cartItem->options)) {
                $this->addOrderMenuOptions($orderMenu->getKey(), $cartItem->id, $cartItem->options);
            }
        }
    }

    /**
     * Add cart menu item options to menu and order by,
     * order_id and menu_id
     *
     * @return bool
     */
    protected function addOrderMenuOptions($orderMenuId, $menuId, $menuOptions)
    {
        $orderId = $this->getKey();
        if (!is_numeric($orderId)) {
            return false;
        }

        foreach ($menuOptions as $menuOption) {
            foreach ($menuOption->values as $menuOptionValue) {
                $this->menu_options()->create([
                    'order_menu_id' => $orderMenuId,
                    'menu_option_id' => $menuOption->id,
                    'menu_option_value_id' => $menuOptionValue->id,
                    'order_option_name' => $menuOptionValue->name,
                    'order_option_price' => $menuOptionValue->price,
                    'quantity' => $menuOptionValue->qty,
                ]);
            }
        }
    }

    /**
     * Add cart totals to order by order_id
     *
     * @return bool
     */
    public function addOrderTotals(array $totals = [])
    {
        $orderId = $this->getKey();
        if (!is_numeric($orderId)) {
            return false;
        }

        foreach ($totals as $total) {
            $this->addOrUpdateOrderTotal($total);
        }

        $this->calculateTotals();
    }

    public function addOrUpdateOrderTotal(array $total)
    {
        return $this->orderTotalsQuery()->updateOrInsert([
            'order_id' => $this->getKey(),
            'code' => $total['code'],
        ], array_except($total, ['order_id', 'code']));
    }

    public function calculateTotals()
    {
        $subtotal = $this->orderMenusQuery()
            ->where('order_id', $this->getKey())
            ->sum('subtotal');

        $total = $this->orderTotalsQuery()
            ->where('order_id', $this->getKey())
            ->where('is_summable', true)
            ->sum('value');

        $orderTotal = max(0, $subtotal + $total);

        $totalItems = $this->orderMenusQuery()
            ->where('order_id', $this->getKey())
            ->sum('quantity');

        $this->orderTotalsQuery()
            ->where('order_id', $this->getKey())
            ->where('code', 'subtotal')
            ->update(['value' => $subtotal]);

        $this->orderTotalsQuery()
            ->where('order_id', $this->getKey())
            ->where('code', 'total')
            ->update(['value' => $orderTotal]);

        $this->newQuery()->where('order_id', $this->getKey())->update([
            'total_items' => $totalItems,
            'order_total' => $orderTotal,
        ]);
    }

    public function orderMenusQuery()
    {
        return DB::table('order_menus');
    }

    public function orderMenuOptionsQuery()
    {
        return DB::table('order_menu_options');
    }

    public function orderTotalsQuery()
    {
        return DB::table('order_totals');
    }
}
