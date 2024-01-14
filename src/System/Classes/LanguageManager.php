<?php

namespace Igniter\System\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Translation\FileLoader;
use Igniter\System\Models\Language;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use ZipArchive;

class LanguageManager
{
    protected FileLoader $loader;

    protected string $langPath;

    protected HubManager $hubManager;

    /**
     * @var array of languages and their directory paths.
     */
    protected $paths = [];

    public function initialize()
    {
        $this->loader = App::make('translation.loader');
        $this->langPath = App::langPath();

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
                'translation' => $translationLine,
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

    public function applyLanguagePack(string $locale, ?string $build = null): array
    {
        $response = $this->getHubManager()->applyLanguagePack($locale, $build);

        return array_get($response, 'data', []);
    }

    public function downloadPack(array $meta): array
    {
        $packCode = array_get($meta, 'code');
        $packHash = array_get($meta, 'hash');

        $filePath = $this->getFilePath($packCode);
        if (!is_dir($fileDir = dirname($filePath))) {
            mkdir($fileDir, 0777, true);
        }

        return $this->getHubManager()->downloadLanguagePack($filePath, $packHash, [
            'locale' => $packCode,
            'build' => array_get($meta, 'version'),
        ]);
    }

    public function extractPack(array $meta): bool
    {
        $packCode = array_get($meta, 'code');

        $filePath = $this->getFilePath($packCode);
        $extractTo = app()->langPath().'/'.$packCode;
        if (!file_exists($extractTo)) {
            mkdir($extractTo, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $zip->extractTo($extractTo);
            $zip->close();
            @unlink($filePath);

            return true;
        }

        throw new SystemException('Failed to extract '.$packCode.' archive file');
    }

    public function installPack(array $item): bool
    {
        $model = Language::firstOrCreate(['code' => $item['code']]);
        $model->name = $item['name'];
        $model->version = $item['version'];
        $model->save();

        return true;
    }

    public function getFilePath(string $packCode): string
    {
        $fileName = md5($packCode).'.zip';

        return storage_path("temp/$fileName");
    }

    protected function getHubManager(): HubManager
    {
        return $this->hubManager;
    }
}
