<?php

namespace Igniter\Admin\FormWidgets;

use Igniter\Admin\Classes\BaseFormWidget;

/**
 * Rich Editor
 * Renders a rich content editor field.
 *
 * Adapted from october\backend\classes\RichEditor
 */
class RichEditor extends BaseFormWidget
{
    //
    // Configurable properties
    //

    /** Determines whether content has HEAD and HTML tags. */
    public bool $fullPage = false;

    public ?string $stretch = null;

    public ?string $size = null;

    public ?string $toolbarButtons = null;

    //
    // Object properties
    //

    protected string $defaultAlias = 'richeditor';

    public function initialize()
    {
        $this->fillFromConfig([
            'fullPage',
            'stretch',
            'size',
            'toolbarButtons',
        ]);
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('richeditor/richeditor');
    }

    public function loadAssets()
    {
        $this->addJs('js/vendor.editor.js', 'vendor-editor-js');
        $this->addCss('richeditor.css', 'richeditor-css');
        $this->addJs('richeditor.js', 'richeditor-js');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['field'] = $this->formField;
        $this->vars['fullPage'] = $this->fullPage;
        $this->vars['stretch'] = $this->stretch;
        $this->vars['size'] = $this->size;
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['toolbarButtons'] = $this->evalToolbarButtons();
    }

    /**
     * Determine the toolbar buttons to use based on config.
     */
    protected function evalToolbarButtons(): ?array
    {
        $buttons = $this->toolbarButtons;

        if (is_string($buttons)) {
            $buttons = array_map(function($button) {
                return $button ?: '|';
            }, explode('|', $buttons));
        }

        return $buttons;
    }
}
