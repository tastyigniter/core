<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Translation\FileLoader;
use Igniter\System\Models\Language;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class LanguageManager
{
    protected FileLoader $loader;

    protected string $langPath;

    protected UpdateManager $updateManager;

    protected HubManager $hubManager;

    /**
     * @var array of languages and their directory paths.
     */
    protected $paths = [];

    public function initialize()
    {
        $this->loader = App::make('translation.loader');
        $this->langPath = App::langPath();

        $this->updateManager = resolve(UpdateManager::class);
        $this->hubManager = resolve(HubManager::class);
    }

    public function namespaces(): array
    {
        $namespaces = $this->loader->namespaces();
        asort($namespaces);

        return $namespaces;
    }

    public function listLanguages(): Collection
    {
        return Language::whereIsEnabled()->get();
    }

    /**
     * Create a Directory Map of all themes
     */
    public function paths(): array
    {
        if ($this->paths) {
            return $this->paths;
        }

        $paths = [];

        if (!File::exists($directory = base_path('language'))) {
            return $paths;
        }

        foreach (File::directories($directory) as $path) {
            $langDir = basename($path);
            $paths[$langDir] = $path;
        }

        return $this->paths = $paths;
    }

    //
    // Translations
    //

    public function listLocaleFiles(string $locale): array
    {
        $result = [];
        $namespaces = $this->loader->namespaces();
        asort($namespaces);
        foreach ($namespaces as $namespace => $folder) {
            foreach (File::glob($folder.'/'.$locale.'/*.php') as $filePath) {
                $result[] = [
                    'namespace' => $namespace,
                    'group' => pathinfo($filePath, PATHINFO_FILENAME),
                    'system' => in_array(ucfirst($namespace), config('igniter-system.modules', [])),
                ];
            }
        }

        return $result;
    }

    public function listTranslations(array $sourceLines, array $translationLines, array $options = []): array
    {
        $file = array_get($options, 'file');
        $stringFilter = array_get($options, 'stringFilter');

        $result = [];
        foreach ($sourceLines as $key => $sourceLine) {
            $translationLine = array_get($translationLines, $key, $sourceLine);

            if ($stringFilter === 'changed' && !array_has($translationLines, $key)) {
                continue;
            }

            if ($stringFilter === 'unchanged' && array_has($translationLines, $key)) {
                continue;
            }

            if ((!is_null($sourceLine) && !is_string($sourceLine))) {
                continue;
            }

            if ((!is_null($translationLine) && !is_string($translationLine))) {
                continue;
            }

            $namespacedKey = sprintf('%s::%s.%s', $file['namespace'], $file['group'], $key);

            $result[$namespacedKey] = [
                'source' => $sourceLine,
                'translation' => (strcmp($sourceLine, $translationLine) === 0) ? '' : $translationLine,
            ];
        }

        return $result;
    }

    public function searchTranslations(array $translations, ?string $term = null): array
    {
        if (!strlen($term)) {
            return $translations;
        }

        $result = [];
        $term = strtolower($term);
        foreach ($translations as $key => $value) {
            if (strlen($term)) {
                if (stripos(strtolower(array_get($value, 'source')), $term) !== false
                    || stripos(strtolower(array_get($value, 'translation')), $term) !== false
                    || stripos(strtolower($key), $term) !== false) {
                    $result[$key] = $value;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function paginateTranslations(array $translations, int $perPage = 50): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        $items = collect($translations);
        $total = $items->count();

        $items = $total ? $items->forPage($page, $perPage) : collect();

        $options = [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ];

        return App::makeWith(LengthAwarePaginator::class, compact(
            'items', 'total', 'perPage', 'page', 'options'
        ));
    }

    public function canUpdate(Language $language): bool
    {
        return !in_array($language->code, ['en', 'en_US', 'en_GB']) && $language->can_update;
    }

    //
    //
    //

    public function searchLanguages(string $term): array
    {
        $items = $this->getHubManager()->listLanguages([
            'search' => $term,
        ]);

        if (isset($items['data'])) {
            foreach ($items['data'] as &$item) {
                $item['require'] = [];
            }
        }

        return $items;
    }

    public function requestUpdateList(string $locale, bool $force = false): array
    {
        $cacheKey = 'translation_string_updates';
        $cacheKey .= '.'.$locale;

        if ($force || !$response = cache()->get($cacheKey)) {
            throw_unless($language = Language::findByCode($locale), new ApplicationException('Language not found'));

            $response['items'] = $this->applyLanguagePack($locale, (array)$language->version);
            $response['last_checked_at'] = now()->toDateTimeString();

            Cache::put($cacheKey, $response, now()->addHours(6));
        }

        return $response;
    }

    public function hasNewStrings(string $locale): int
    {
        $cacheKey = 'translation_string_updates';
        $cacheKey .= '.'.$locale;

        return count(array_get(cache()->get($cacheKey), 'items', []));
    }

    public function applyLanguagePack(string $locale, ?array $builds = null): array
    {
        $items = collect($this->updateManager->getInstalledItems())
            ->map(function($item) use ($builds) {
                $item['build'] = array_get($builds, $item['name']);
                return $item;
            })
            ->all();

        $response = $this->getHubManager()->applyLanguagePack($locale, $items);

        return array_get($response, 'data', []);
    }

    public function installLanguagePack(string $locale, array $meta): bool
    {
        $eTag = array_get($meta, 'hash');

        $fileStrings = $this->getHubManager()->downloadLanguagePack($eTag, [
            'locale' => $locale,
            'item' => $meta,
        ]);

        collect($fileStrings)
            ->each(function($strings, $filename) use ($locale, $meta) {
                if (ends_with($filename, '.php')) {
                    $this->createLanguageFile($locale, $meta['name'], $filename, $strings);
                }
            });

        return true;
    }

    protected function createLanguageFile(string $locale, string $code, string $filename, array $strings)
    {
        $filePath = $this->langPath.'/vendor/'.str_replace('.', '-', $code).'/'.$locale.'/'.$filename;

        File::makeDirectory(dirname($filePath), 0777, true, true);

        File::put($filePath, "<?php\n\nreturn ".var_export($strings, true).";\n");
    }

    protected function getHubManager(): HubManager
    {
        return $this->hubManager;
    }
}
