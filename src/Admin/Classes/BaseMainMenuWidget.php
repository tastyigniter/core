<?php

namespace Igniter\Admin\Classes;

class BaseMainMenuWidget extends BaseWidget
{
    protected MainMenuItem $menuItem;

    /**
     * Constructor
     *
     * @param $controller \Illuminate\Routing\Controller Active controller object.
     * @param $menuItem \Igniter\Admin\Classes\MainMenuItem Object containing general form field information.
     * @param $config array Configuration the relates to this widget.
     */
    public function __construct(AdminController $controller, MainMenuItem $menuItem, array $config = [])
    {
        $this->menuItem = $menuItem;

        $this->config = $this->makeConfig($config);

        parent::__construct($controller, $config);
    }

    /**
     * Returns a unique ID for this widget. Useful in creating HTML markup.
     */
    public function getId(?string $suffix = null): string
    {
        $id = parent::getId($suffix);
        $id .= '-'.$this->menuItem->getId();

        return name_to_id($id);
    }
}
