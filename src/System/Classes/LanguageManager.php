<?php

declare(strict_types=1);

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Models\Language;
use Igniter\System\Models\Translation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Translation\FileLoader as IlluminateFileLoader;
use stdClass;

class LanguageManager
{
    protected IlluminateFileLoader $loader;

    protected string $langPath;

    protected UpdateManager $updateManager;

    protected HubManager $hubManager;

    /**
     * @var array of languages and their directory paths.
     */
    protected $paths = [];

    public function initialize(): void
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
            $langDir = basename((string) $path);
            $paths[$langDir] = $path;
        }

        return $this->paths = $paths;
    }

    //
    // Translations
    //

    public function listLocalePackages(?string $locale = null): array
    {
        $locale ??= 'en';

        $result = [];
        $extensionManager = resolve(ExtensionManager::class);
        $namespaces = $this->loader->namespaces();
        asort($namespaces);
        foreach ($namespaces as $namespace => $folder) {
            $name = $namespace === 'igniter' ? 'Application' : 'Unknown';
            if ($extension = $extensionManager->findExtension($namespace)) {
                $name = array_get($extension->extensionMeta(), 'name');
            }

            $result[] = (object)[
                'code' => $namespace,
                'name' => $name,
                'files' => File::glob($folder.'/'.$locale.'/*.php'),
            ];
        }

        return $result;
    }

    public function listTranslations(
        Language $model,
        ?string $packageCode = null,
        ?string $filter = null,
        ?string $searchTerm = null,
    ): stdClass {
        $result = (object)[
            'total' => null,
            'translated' => null,
            'untranslated' => null,
            'progress' => null,
            'strings' => [],
        ];

        collect($this->listLocalePackages())
            ->filter(fn(stdClass $localePackage): bool => !$packageCode || $localePackage->code === $packageCode)
            ->each(function(stdClass $localePackage) use ($filter, $result, $model) {
                collect($localePackage->files)->each(function($filePath) use ($filter, $result, $localePackage, $model) {
                    $filePath = pathinfo($filePath, PATHINFO_FILENAME);

                    $sourceLines = $model->getLines('en', $filePath, $localePackage->code);
                    $translationLines = $model->getTranslations($filePath, $localePackage->code);

                    $result->total += count($sourceLines);
                    $result->translated += count($translationLines);

                    $localeGroup = sprintf('%s::%s', $localePackage->code, $filePath);
                    $translations = $this->listTranslationStrings($sourceLines, $translationLines, $localeGroup, $filter);

                    $result->strings = array_merge($result->strings, $translations);
                });
            });

        if (!is_null($searchTerm) && strlen($searchTerm)) {
            $result->strings = $this->searchTranslations($result->strings, $searchTerm);
        }

        $result->strings = $this->paginateTranslations($result->strings);

        $result->untranslated = $result->total - $result->translated;
        $result->progress = $result->total ? round(($result->translated * 100) / $result->total, 2) : 0;

        return $result;
    }

    public function publishTranslations(Language $model): array
    {
        $translations = $model->translations()
            ->get()
            // @phpstan-ignore-next-line
            ->groupBy(fn(Translation $translation): string => sprintf('%s::%s', $translation->namespace, $translation->group))
            ->map(fn(Collection $translations, string $group): array => [
                'name' => $group,
                'strings' => $translations
                    // @phpstan-ignore-next-line
                    ->map(fn(Translation $translation): array => [
                        'key' => $translation->item,
                        'value' => $translation->text,
                    ])
                    ->all(),
            ])
            ->all();

        return $this->hubManager->publishTranslations($model->code, $translations);
    }

    protected function listTranslationStrings(array $sourceLines, array $translationLines, string $localeGroup, ?string $filter): array
    {
        $result = [];
        foreach ($sourceLines as $key => $sourceLine) {
            $translationLine = array_get($translationLines, $key, $sourceLine);

            if (
                ($filter === 'changed' && !array_has($translationLines, $key))
                || ($filter === 'unchanged' && array_has($translationLines, $key))
                || ((!is_null($sourceLine) && !is_string($sourceLine)))
                || (!is_null($translationLine) && !is_string($translationLine))
            ) {
                continue;
            }

            $result[sprintf('%s.%s', $localeGroup, $key)] = [
                'source' => $sourceLine,
                'translation' => (strcmp((string) $sourceLine, (string) $translationLine) === 0) ? '' : $translationLine,
            ];
        }

        return $result;
    }

    protected function searchTranslations(array $translations, ?string $term = null): array
    {
        $result = [];
        $term = strtolower((string) $term);
        foreach ($translations as $key => $value) {
            if (stripos(strtolower((string) array_get($value, 'source')), $term) !== false
                || stripos(strtolower((string) array_get($value, 'translation')), $term) !== false
                || stripos(strtolower($key), $term) !== false) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    protected function paginateTranslations(array $translations, int $perPage = 50): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        $items = collect($translations);
        $total = $items->count();
        $items = $total ? $items->forPage($page, $perPage) : collect();

        $options = [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ];

        return App::makeWith(LengthAwarePaginator::class, ['items' => $items, 'total' => $total, 'perPage' => $perPage, 'page' => $page, 'options' => $options]);
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

    public function findLanguage(string $locale): array
    {
        $result = $this->getHubManager()->getLanguage($locale);

        return array_get($result, 'data', []);
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

    public function applyLanguagePack(string $locale, ?array $builds = null): array
    {
        $items = collect($this->updateManager->getInstalledItems())
            ->map(function(array $item) use ($builds) {
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
