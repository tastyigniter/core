<?php

namespace Igniter\Main\Components;

use Igniter\Flame\Pagic\TemplateCode;
use Igniter\System\Classes\BaseComponent;

class BlankComponent extends BaseComponent
{
    /** This component is hidden from the admin UI. */
    public bool $isHidden = true;

    /** Error message that is shown with this error component. */
    protected ?string $errorMessage;

    public function __construct(?TemplateCode $page, array $properties, ?string $errorMessage = null)
    {
        $this->errorMessage = $errorMessage;

        parent::__construct($page, $properties);
    }

    public function onRender(): string
    {
        return $this->errorMessage;
    }
}
