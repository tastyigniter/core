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

    /** The directory name associated with the model, eg: _pages. */
    public const DIR_NAME = '_pages';

    /**
     * Helper that makes a URL for a page in the active theme.
     */
    public static function url(string $page, array $params = []): string
    {
        $controller = MainController::getController();

        return $controller->pageUrl($page, $params);
    }

    /**
     * Handler for the pages.menuitem.getTypeInfo event.
     */
    public static function getMenuTypeInfo(string $type): ?array
    {
        if ($type !== 'theme-page') {
            return null;
        }

        if (!$themeCode = resolve(ThemeManager::class)->getActiveThemeCode()) {
            return null;
        }

        $references = self::getDropdownOptions($themeCode);

        return [
            'references' => $references,
        ];
    }

    /**
     * Handler for the pages.menuitem.resolveItem event.
     */
    public static function resolveMenuItem(mixed $item, string $url, Theme $theme): ?array
    {
        if (!$item->reference) {
            return null;
        }

        $controller = MainController::getController();
        $pageUrl = $controller->pageUrl($item->reference, [], false);

        return [
            'url' => $pageUrl,
            'isActive' => $pageUrl == $url,
        ];
    }

    /**
     * Returns name of a PHP class to use as parent
     * for the PHP class created for the template's PHP section.
     */
    public function getCodeClassParent(): string
    {
        return \Igniter\Main\Template\Code\PageCode::class;
    }

    public static function resolveRouteBinding(string $value, ?string $field = null): mixed
    {
        if (($page = event('main.page.beforeRoute', [$value, $field], true)) !== null) {
            return $page;
        }

        $page = self::find($value);

        throw_unless($page, (new ModelNotFoundException)->setModel(__CLASS__));

        throw_if($page->isHidden && !AdminAuth::check(), (new ModelNotFoundException)->setModel(__CLASS__));

        return $page;
    }
}
