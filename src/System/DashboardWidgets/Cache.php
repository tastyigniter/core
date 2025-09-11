<?php

declare(strict_types=1);

namespace Igniter\System\DashboardWidgets;

use Facades\Igniter\System\Helpers\CacheHelper;
use Igniter\Admin\Classes\BaseDashboardWidget;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Support\Number;
use Override;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Cache extends BaseDashboardWidget
{
    /**
     * @var string A unique alias to identify this widget.
     */
    protected string $defaultAlias = 'cache';

    protected static array $caches = [
        [
            'path' => 'framework/views',
            'color' => '#2980b9',
        ],
        [
            'path' => 'igniter/cache',
            'color' => '#16a085',
        ],
        [
            'path' => 'framework/cache',
            'color' => '#8e44ad',
        ],
        [
            'path' => 'igniter/combiner',
            'color' => '#c0392b',
        ],
    ];

    #[Override]
    public function render(): string
    {
        $this->prepareVars();

        return $this->makePartial('cache/cache');
    }

    #[Override]
    public function defineProperties(): array
    {
        return [
            'title' => [
                'label' => 'igniter::admin.dashboard.label_widget_title',
                'default' => 'igniter::admin.dashboard.text_cache_usage',
                'type' => 'text',
            ],
        ];
    }

    protected function prepareVars()
    {
        $totalCacheSize = 0;
        $cacheSizes = [];
        foreach (self::$caches as $cacheInfo) {
            if (!File::isDirectory($directory = storage_path().'/'.$cacheInfo['path'])) {
                continue;
            }

            $size = $this->folderSize($directory);
            $cacheSizes[] = (object)[
                'label' => $cacheInfo['path'],
                'color' => $cacheInfo['color'],
                'size' => $size,
                'formattedSize' => Number::fileSize($size),
            ];

            $totalCacheSize += $size;
        }

        $this->vars['cacheSizes'] = $cacheSizes;
        $this->vars['totalCacheSize'] = $totalCacheSize;
        $this->vars['formattedTotalCacheSize'] = Number::fileSize($totalCacheSize);
    }

    public function onClearCache(): array
    {
        rescue(fn() => CacheHelper::clear());

        $this->prepareVars();

        return [
            '#'.$this->getId() => $this->makePartial('cache/cache'),
        ];
    }

    protected function folderSize(string $directory): int
    {
        $size = 0;

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
