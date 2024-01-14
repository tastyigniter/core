<?php

namespace Igniter\Main\Template;

use Igniter\Flame\Pagic\Model;

/**
 * Layout Template Class
 */
class Layout extends Model
{
    use Concerns\HasComponents;
    use Concerns\HasViewBag;

    /** The directory name associated with the model, eg: pages. */
    public const DIR_NAME = '_layouts';

    public static function initFallback(string $source): self
    {
        $model = self::on($source);
        $model->markup = '<?= page(); ?>';
        $model->fileName = 'default';

        return $model;
    }

    /**
     * Returns name of a PHP class to use as parent
     * for the PHP class created for the template's PHP section.
     */
    public function getCodeClassParent(): string
    {
        return \Igniter\Main\Template\Code\LayoutCode::class;
    }
}
