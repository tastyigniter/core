<?php

namespace Igniter\Admin\Classes;

use Illuminate\Support\Collection;

/**
 * Bulk Action Widget base class
 * Widgets used specifically for lists
 */
class BaseBulkActionWidget extends BaseWidget
{
    public ?string $code = null;

    public ?string $label = null;

    public ?string $type = null;

    public ?string $popupTitle = null;

    //
    // Object properties
    //

    protected array $defaultConfig = [];

    protected ToolbarButton $actionButton;

    public function __construct(AdminController $controller, ToolbarButton $actionButton, array $config = [])
    {
        $this->actionButton = $actionButton;

        $this->config = $this->makeConfig(array_merge_recursive($this->defaultConfig, $config));

        $this->fillFromConfig([
            'label',
            'popupTitle',
        ]);

        parent::__construct($controller, $config);
    }

    /**
     * Extra field configuration for the action.
     */
    public function defineFormFields(): array
    {
        return [];
    }

    /**
     * Defines validation rules for the custom fields.
     */
    public function defineValidationRules(): array
    {
        return [];
    }

    public function getActionButton(): ToolbarButton
    {
        return $this->actionButton;
    }

    public function handleAction(array $requestData, Collection $records) {}
}
