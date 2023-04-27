<?php

namespace Igniter\Main\Template;

use Igniter\Flame\Pagic\Model;

/**
 * Layout Template Class
 */
class Layout extends Model
{
    use Concerns\UsesBlueprint;
    use Concerns\HasComponents;
    use Concerns\HasViewBag;

    /**
     * @var string The directory name associated with the model, eg: pages.
     */
    public const DIR_NAME = '_layouts';

    public $controller;

    public static function initFallback($source)
    {
        $model = self::on($source);
        $model->markup = '<?= page(); ?>';
        $model->fileName = 'default';

        return $model;
    }

    /**
     * Returns name of a PHP class to use as parent
     * for the PHP class created for the template's PHP section.
     * @return mixed Returns the class name or null.
     */
    public function getCodeClassParent()
    {
        return \Igniter\Main\Template\Code\LayoutCode::class;
    }
}
