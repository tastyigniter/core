<?php

declare(strict_types=1);

namespace Igniter\System\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void registerCallback(callable $callback)
 * @method static void registerSourcePath(string $path)
 * @method static void addFromManifest(string $path)
 * @method static void addAssetsFromThemeManifest(\Igniter\Main\Classes\Theme $theme)
 * @method static void addTags(array $tags = [])
 * @method static \Igniter\System\Libraries\Assets addTag(string $type, array|string|null $tag, array $options = [])
 * @method static string|null getFavIcon()
 * @method static string|null getMetas()
 * @method static string|null getRss()
 * @method static string|null getCss()
 * @method static string|null getJs()
 * @method static string|null getJsVars()
 * @method static \Igniter\System\Libraries\Assets addFavIcon(array|string $icon)
 * @method static \Igniter\System\Libraries\Assets addMeta(array $meta = [])
 * @method static \Igniter\System\Libraries\Assets addRss(string $path, array|string|null $attributes = [])
 * @method static \Igniter\System\Libraries\Assets addCss(string $path, array|string|null $attributes = null)
 * @method static \Igniter\System\Libraries\Assets addJs(string $path, array|string|null $attributes = null)
 * @method static void putJsVars(array $variables)
 * @method static void mergeJsVars(string $key, array $value)
 * @method static void flush()
 * @method static string combine(string $type, array $assets = [])
 * @method static void combineToFile(array $assets, string $destination)
 * @method static \Illuminate\Http\Response combineGetContents(string $cacheKey)
 * @method static array buildBundles(\Igniter\Main\Classes\Theme $theme)
 * @method static \Igniter\System\Libraries\Assets registerFilter(array|string $extension, \Igniter\Flame\Assetic\Filter\FilterInterface|null $filter)
 * @method static void registerBundle(string $extension, array|string $files, string|null $destination = null, string $appContext = 'main')
 * @method static array|null getBundles(string|null $extension = null, string $appContext = 'main')
 * @method static array|null getFilters(void $extension = null)
 * @method static \Igniter\System\Libraries\Assets resetFilters(string|null $extension = null)
 *
 * @see \Igniter\System\Libraries\Assets
 */
class Assets extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     * @see \Igniter\System\Libraries\Template
     */
    protected static function getFacadeAccessor()
    {
        return 'assets';
    }
}
