<?php

declare(strict_types=1);

namespace Igniter\Main\Template;

use Igniter\Flame\Pagic\Finder;
use Igniter\Flame\Pagic\Model;
use Igniter\Main\Classes\Theme;
use Illuminate\Support\Collection;
use Override;

class File extends Model
{
    /**
     * Empty DIR_NAME means files are stored at {@see Theme::getSourcePath()},
     * not the theme package root ({@see Theme::getPath()}).
     */
    public const string DIR_NAME = '';

    /** Virtual TemplateEditor / ThemeManager type key (not a filesystem directory). */
    public const string TYPE_KEY = '_files';

    /** Directories under the theme source path excluded from the Files picker. */
    public const array RESERVED_DIRS = [
        '_pages',
        '_partials',
        '_layouts',
        '_content',
        '_meta',
        'meta',
        'assets',
        'resources',
        'public',
    ];

    public array $settings = [];

    /** Allow arbitrary path nesting for Files templates. */
    protected ?int $maxNesting = null;

    public static function isReservedPath(string $fileName): bool
    {
        $fileName = str_replace('\\', '/', ltrim($fileName, '/'));
        $fileName = str_before($fileName, '.'.static::DEFAULT_EXTENSION);
        $firstSegment = strstr($fileName, '/', true) ?: $fileName;

        return in_array($firstSegment, self::RESERVED_DIRS, true);
    }

    #[Override]
    public static function listInTheme(string|Theme|null $source = null, bool $skipCache = false): Collection
    {
        return parent::listInTheme($source, $skipCache)
            ->filter(fn(Model $model): bool => !static::isReservedPath((string)$model->getFileName()))
            ->values();
    }

    #[Override]
    public function newFinder(): Finder
    {
        return parent::newFinder()
            ->depth(null)
            ->excludeDirs(self::RESERVED_DIRS)
            ->names(['*.'.static::DEFAULT_EXTENSION]);
    }
}
