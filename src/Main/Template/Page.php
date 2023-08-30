<?php

namespace Igniter\Main\Template;

use Igniter\Flame\Pagic\Model;
use Igniter\Main\Classes\MainController;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\User\Facades\AdminAuth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Page Template Class
 */
class Page extends Model
{
    use Concerns\HasComponents;
    use Concerns\HasViewBag;
    use Concerns\UsesBlueprint;

    /**
     * @var string The directory name associated with the model, eg: _pages.
     */
    public const DIR_NAME = '_pages';

    /**
     * Helper that makes a URL for a page in the active theme.
     * @return string
     */
    public static function url($page, array $params = [])
    {
        $controller = MainController::getController() ?: new MainController;

        return $controller->pageUrl($page, $params);
    }

    /**
     * Handler for the pages.menuitem.getTypeInfo event.
     * @return array|void
     */
    public static function getMenuTypeInfo(string $type)
    {
        if ($type !== 'theme-page') {
            return;
        }

        if (!$themeCode = resolve(ThemeManager::class)->getActiveThemeCode()) {
            return;
        }

        $references = self::getDropdownOptions($themeCode);

        return [
            'references' => $references,
        ];
    }

    /**
     * Handler for the pages.menuitem.resolveItem event.
     * @return array|void
     */
    public static function resolveMenuItem($item, string $url, Theme $theme)
    {
        if (!$item->reference) {
            return;
        }

        $controller = MainController::getController() ?: new MainController;
        $pageUrl = $controller->pageUrl($item->reference, [], false);

        return [
            'url' => $pageUrl,
            'isActive' => $pageUrl == $url,
        ];
    }

    /**
     * Returns name of a PHP class to use as parent
     * for the PHP class created for the template's PHP section.
     *
     * @return mixed Returns the class name or null.
     */
    public function getCodeClassParent()
    {
        return \Igniter\Main\Template\Code\PageCode::class;
    }

    public static function resolveRouteBinding($value, $field = null)
    {
        if (($page = event('main.page.beforeRoute', [$value, $field], true)) !== null) {
            return $page;
        }

        $page = self::find($value);

        throw_unless($page, (new ModelNotFoundException)->setModel(Page::class));

        throw_if(!AdminAuth::check() && $page->isHidden, (new ModelNotFoundException)->setModel(Page::class));

        return $page;
    }
}
