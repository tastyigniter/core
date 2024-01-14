<?php

namespace Igniter\Main\Template;

use Igniter\Flame\Pagic\Model;

class Partial extends Model
{
    /** The directory name associated with the model */
    public const DIR_NAME = '_partials';

    public array $settings = [];

    /**
     * Returns name of a PHP class to use as parent
     * for the PHP class created for the template's PHP section.
     */
    public function getCodeClassParent(): string
    {
        return \Igniter\Main\Template\Code\PartialCode::class;
    }
}
