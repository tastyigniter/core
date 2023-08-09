<?php

namespace Igniter\Main\Classes;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Igniter;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Models\Theme as ThemeModel;
use Igniter\Main\Template\Page;
use Igniter\System\Classes\ComposerManager;
use Igniter\System\Classes\PackageManifest;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Libraries\Assets;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;

/**
 * Theme Manager Class
 */
class ThemeManager
{
    protected $themeModel = ThemeModel::class;

    /**
     * @var array of disabled themes.
     */
    public $disabledThemes = [];

    /**
     * @var array used for storing theme information objects.
     */
    public $themes = [];

    public $activeTheme;

    /**
     * @var array of themes and their directory paths.
     */
    protected $paths = [];

    protected $config = [
        'allowedImageExt' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'],
        'allowedFileExt' => ['html', 'txt', 'xml', 'js', 'css', 'php', 'json'],
    ];

    protected $booted = false;

    protected static $directories = [];

    public function initialize()
    {
        $this->disabledThemes = resolve(PackageManifest::class)->disabledAddons();
    }

    public static function addDirectory($directory)
    {
        self::$directories[] = $directory;
    }

    public function addAssetsFromActiveThemeManifest(Assets $manager)
    {
        if (!$theme = $this->getActiveTheme()) {
            return;
        }

        if (File::exists($theme->getSourcePath().'/_meta/assets.json')) {
            $manager->addFromManifest($theme->getSourcePath().'/_meta/assets.json');
        } elseif ($theme->hasParent() && $parent = $theme->getParent()) {
            $manager->addFromManifest($parent->getSourcePath().'/_meta/assets.json');
        }
    }

    public function applyAssetVariablesOnCombinerFilters(array $filters, Theme $theme = null)
    {
        $theme = !is_null($theme) ? $theme : $this->getActiveTheme();

        if (!$theme || !$theme->hasCustomData()) {
            return;
        }

        $assetVars = $theme->getAssetVariables();
        foreach ($filters as $filter) {
            if (method_exists($filter, 'setVariables')) {
                $filter->setVariables($assetVars);
            }
        }
    }

    //
    // Registration Methods
    //

    /**
     * Returns a list of all themes in the system.
     * @return array A list of all themes in the system.
     */
    public function listThemes()
    {
        return $this->themes;
    }

    /**
     * Finds all available themes and loads them in to the $themes array.
     * @return array
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function loadThemes()
    {
        foreach (resolve(PackageManifest::class)->themes() as $code => $config) {
            $this->loadThemeFromConfig($code, $config);
        }

        foreach ($this->folders() as $path) {
            $this->loadTheme($path);
        }

        return $this->themes;
    }

    public function loadThemeFromConfig($code, $config)
    {
        if (isset($this->themes[$code])) {
            return $this->themes[$code];
        }

        if (!$this->checkName($code)) {
            return false;
        }

        $config = $this->validateMetaFile($config, $code);

        $path = base_path(array_get($config, 'directory'));
        $themeObject = new Theme($path, $config);

        $themeObject->active = $this->isActive($code);

        $this->themes[$code] = $themeObject;
        $this->paths[$code] = $themeObject->getPath();

        return $themeObject;
    }

    /**
     * Loads a single theme in to the manager.
     *
     * @param string $path
     *
     * @return bool|object
     */
    public function loadTheme($path)
    {
        if (!$config = $this->getMetaFromFile($path, false)) {
            return false;
        }

        $config['directory'] = str_after($path, base_path());

        return $this->loadThemeFromConfig(basename($path), $config);
    }

    public function bootThemes()
    {
        if ($this->booted) {
            return;
        }

        if (!$this->themes) {
            $this->loadThemes();
        }

        collect($this->themes)->each(function (Theme $theme) {
            $this->bootTheme($theme);
        });

        $this->booted = true;
    }

    public function bootTheme(Theme $theme)
    {
        Page::getSourceResolver()->addSource($theme->getName(), $theme->makeFileSource());

        if ($theme->isActive()) {
            Page::getSourceResolver()->setDefaultSourceName($theme->getName());
        }

        Igniter::loadResourcesFrom($theme->getAssetPath(), $theme->getName());

        if ($theme->hasParent() && $theme->getParent() && File::isDirectory($path = $theme->getParent()->getAssetPath())) {
            Igniter::loadResourcesFrom($path, $theme->getParent()->getName());
        }

        if ($theme->isActive()) {
            if ($pathsToPublish = $theme->getPathsToPublish()) {
                foreach (['laravel-assets', 'igniter-assets'] as $group) {
                    if (!array_key_exists($group, ServiceProvider::$publishGroups)) {
                        ServiceProvider::$publishGroups[$group] = [];
                    }

                    ServiceProvider::$publishGroups[$group] = array_merge(
                        ServiceProvider::$publishGroups[$group], $pathsToPublish
                    );
                }
            }

            if ($theme->hasParent() && $parent = $theme->getParent()) {
                Igniter::loadViewsFrom($parent->getPath().'/'.Page::DIR_NAME, 'igniter.main');
            }
            Igniter::loadViewsFrom($theme->getPath().'/'.Page::DIR_NAME, 'igniter.main');
        }
    }

    //
    // Management Methods
    //

    public function getActiveTheme(): ?Theme
    {
        return $this->findTheme($this->getActiveThemeCode());
    }

    public function getActiveThemeCode(): ?string
    {
        return Theme::getActiveCode();
    }

    /**
     * Returns a theme object based on its name.
     */
    public function findTheme(string $themeCode = null): ?Theme
    {
        return $this->hasTheme($themeCode) ? $this->themes[$themeCode] : null;
    }

    /**
     * Checks to see if an extension has been registered.
     */
    public function hasTheme(string $themeCode = null): bool
    {
        return isset($this->themes[$themeCode]);
    }

    /**
     * Returns the theme domain by looking in its path.
     */
    public function findParent(string $themeCode = null): ?Theme
    {
        return $this->findTheme($this->findTheme($themeCode)?->getParentName());
    }

    /**
     * Returns the parent theme code.
     */
    public function findParentCode(string $themeCode = null): ?string
    {
        return $this->findTheme($themeCode)?->getParentName();
    }

    public function paths()
    {
        return $this->paths;
    }

    /**
     * Create a Directory Map of all themes
     * @return array A list of all themes in the system.
     */
    public function folders()
    {
        $paths = [];

        $directories = self::$directories;
        if (File::isDirectory($themesPath = Igniter::themesPath())) {
            array_unshift($directories, $themesPath);
        }

        foreach ($directories as $directory) {
            foreach (File::glob($directory.'/*/theme.json') as $path) {
                $paths[] = dirname($path);
            }
        }

        return $paths;
    }

    /**
     * Determines if a theme is activated by looking at the default themes config.
     *
     * @return bool
     */
    public function isActive($themeCode)
    {
        if (!$this->checkName($themeCode)) {
            return false;
        }

        return rtrim($themeCode, '/') == $this->getActiveThemeCode();
    }

    /**
     * Determines if a theme is disabled by looking at the installed themes config.
     *
     * @return bool
     */
    public function isDisabled($themeCode)
    {
        traceLog('Deprecated. Use $instance::isActive($themeCode) instead');

        return !$this->checkName($themeCode) || !array_get($this->disabledThemes, $themeCode, false);
    }

    /**
     * Checks to see if a theme has been registered.
     *
     * @return bool
     */
    public function checkName($themeCode)
    {
        if ($themeCode == 'errors') {
            return null;
        }

        return (str_starts_with($themeCode, '_') || preg_match('/\s/', $themeCode)) ? null : $themeCode;
    }

    public function isLocked($themeCode)
    {
        return (bool)optional($this->findTheme($themeCode))->locked;
    }

    public function checkParent($themeCode)
    {
        foreach ($this->themes as $theme) {
            if ($theme->hasParent() && $theme->getParentName() == $themeCode) {
                return true;
            }
        }

        return false;
    }

    public function isLockedPath($path)
    {
        if (starts_with($path, Igniter::themesPath().'/')) {
            $path = substr($path, strlen(Igniter::themesPath().'/'));
        }

        $themeCode = str_before($path, '/');

        return $this->isLocked($themeCode);
    }

    //
    // Theme Helper Methods
    //

    /**
     * Returns a theme path based on its name.
     *
     * @return string|null
     */
    public function findPath($themeCode)
    {
        return $this->paths()[$themeCode] ?? null;
    }

    /**
     * Find a file.
     * Scans for files located within themes directories. Also scans each theme
     * directories for layouts, partials, and content. Generates fatal error if file
     * not found.
     *
     * @param string $filename The file.
     * @param string $themeCode The theme code.
     * @param string $base The folder within the theme eg. layouts, partials, content
     *
     * @return string|bool
     */
    public function findFile($filename, $themeCode, $base = null)
    {
        $path = $this->findPath($themeCode);

        $themePath = rtrim($path, '/');
        if (is_null($base)) {
            $base = ['/'];
        } elseif (!is_array($base)) {
            $base = [$base];
        }

        foreach ($base as $folder) {
            if (File::isFile($path = $themePath.$folder.$filename)) {
                return $path;
            }
        }

        return false;
    }

    /**
     * Load a single theme generic file into an array. The file will be
     * found by looking in the _layouts, _pages, _partials, _content, themes folders.
     *
     * @param string $filePath The name of the file to locate.
     * @param string $themeCode The theme to check.
     *
     * @return \Igniter\Flame\Pagic\Contracts\TemplateInterface
     */
    public function readFile($filePath, $themeCode)
    {
        $theme = $this->findTheme($themeCode);

        [$dirName, $fileName] = $this->getFileNameParts($filePath, $theme);

        if (!$template = $theme->onTemplate($dirName)->find($fileName)) {
            throw new SystemException("Theme template file not found: $filePath");
        }

        return $template;
    }

    public function newFile($filePath, $themeCode)
    {
        $theme = $this->findTheme($themeCode);
        [$dirName, $fileName] = $this->getFileNameParts($filePath, $theme);
        $path = $theme->getPath().'/'.$dirName.'/'.$fileName;

        if (File::isFile($path)) {
            throw new SystemException("Theme template file already exists: $filePath");
        }

        if (!File::exists($path)) {
            File::makeDirectory(File::dirname($path), 0777, true, true);
        }

        File::put($path, "\n");
    }

    /**
     * Write an existing theme layout, page, partial or content file.
     *
     * @param string $filePath The name of the file to locate.
     * @param string $themeCode The theme to check.
     *
     * @return bool
     */
    public function writeFile($filePath, array $attributes, $themeCode)
    {
        $theme = $this->findTheme($themeCode);

        [$dirName, $fileName] = $this->getFileNameParts($filePath, $theme);

        if (!$template = $theme->onTemplate($dirName)->find($fileName)) {
            throw new SystemException("Theme template file not found: $filePath");
        }

        return $template->fill($attributes)->save();
    }

    /**
     * Rename a theme layout, page, partial or content in the file system.
     *
     * @param string $filePath The name of the file to locate.
     * @param string $newFilePath
     * @param string $themeCode The theme to check.
     *
     * @return bool
     */
    public function renameFile($filePath, $newFilePath, $themeCode)
    {
        $theme = $this->findTheme($themeCode);

        [$dirName, $fileName] = $this->getFileNameParts($filePath, $theme);
        [$newDirName, $newFileName] = $this->getFileNameParts($newFilePath, $theme);

        if (!$template = $theme->onTemplate($dirName)->find($fileName)) {
            throw new SystemException("Theme template file not found: $filePath");
        }

        if ($this->isLockedPath($template->getFilePath())) {
            throw new SystemException(lang('igniter::system.themes.alert_theme_path_locked'));
        }

        $oldFilePath = $theme->path.'/'.$dirName.'/'.$fileName;
        $newFilePath = $theme->path.'/'.$newDirName.'/'.$newFileName;

        if ($oldFilePath == $newFilePath) {
            throw new SystemException("Theme template file already exists: $filePath");
        }

        return $template->update(['fileName' => $newFileName]);
    }

    /**
     * Delete a theme layout, page, partial or content from the file system.
     *
     * @param string $filePath The name of the file to locate.
     * @param string $themeCode The theme to check.
     *
     * @return bool
     */
    public function deleteFile($filePath, $themeCode)
    {
        $theme = $this->findTheme($themeCode);

        [$dirName, $fileName] = $this->getFileNameParts($filePath, $theme);

        if (!$template = $theme->onTemplate($dirName)->find($fileName)) {
            throw new SystemException("Theme template file not found: $filePath");
        }

        if ($this->isLockedPath($template->getFilePath())) {
            throw new SystemException(lang('igniter::system.themes.alert_theme_path_locked'));
        }

        return $template->delete();
    }

    /**
     * Delete existing theme folder from filesystem.
     *
     * @param null $themeCode The theme to delete
     *
     * @return bool
     */
    public function removeTheme($themeCode)
    {
        $themePath = $this->findPath($themeCode);
        if (!is_dir($themePath)) {
            return false;
        }

        File::deleteDirectory($themePath);

        return true;
    }

    /**
     * Delete a single theme by code
     */
    public function deleteTheme(string $themeCode, bool $deleteData = true): void
    {
        $composerManager = resolve(ComposerManager::class);
        if ($packageName = $composerManager->getPackageName($themeCode)) {
            $composerManager->uninstall([$packageName => false]);
        }

        $this->removeTheme($themeCode);

        if ($deleteData) {
            ThemeModel::where('code', $themeCode)->delete();

            resolve(UpdateManager::class)->purgeExtension($themeCode);
        }

        $this->updateInstalledThemes($themeCode, null);
    }

    public function installTheme($code, $version = null)
    {
        $model = $this->themeModel::firstOrNew(['code' => $code]);

        if (!$themeObj = $this->findTheme($model->code)) {
            return false;
        }

        $model->name = $themeObj->label ?? title_case($code);
        $model->code = $code;
        $model->version = $version ?? resolve(PackageManifest::class)->getVersion($code) ?? $model->version;
        $model->description = $themeObj->description ?? '';
        $model->save();

        $this->updateInstalledThemes($model->code);

        return true;
    }

    /**
     * Update installed extensions config value
     */
    public function updateInstalledThemes($code, $enable = true)
    {
        if ($enable) {
            array_pull($this->disabledThemes, $code);
        } else {
            $this->disabledThemes[$code] = true;
        }

        resolve(PackageManifest::class)->writeDisabled($this->disabledThemes);
    }

    /**
     * @param \Igniter\Main\Models\Theme $model
     * @return \Igniter\Main\Models\Theme
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function createChildTheme($model)
    {
        $parentTheme = $this->findTheme($model->code);
        if ($parentTheme->hasParent()) {
            throw new SystemException('Can not create a child theme from another child theme');
        }

        $childThemeCode = $this->themeModel::generateUniqueCode($model->code);
        $childThemePath = Igniter::themesPath().'/'.$childThemeCode;

        $themeConfig = [
            'code' => $childThemeCode,
            'name' => $parentTheme->label.' [child]',
            'description' => $parentTheme->description,
        ];

        $this->writeChildThemeMetaFile(
            $childThemePath, $parentTheme, $themeConfig
        );

        $themeConfig['data'] = $model->data ?? [];

        $theme = $this->themeModel::create($themeConfig);

        $this->booted = false;
        $this->themes = [];
        $this->bootThemes();

        return $theme;
    }

    /**
     * Read configuration from Config/Meta file
     *
     * @param string $themeCode
     *
     * @return array|null
     * @throws \Igniter\Flame\Exception\SystemException
     */
    public function getMetaFromFile($path, $throw = true)
    {
        if (File::exists($metaPath = $path.'/theme.json')) {
            return json_decode(File::get($metaPath), true);
        }

        if ($throw) {
            throw new SystemException('Theme does not have a registration file in: '.$metaPath);
        }
    }

    public function getFileNameParts($path, Theme $theme)
    {
        $parts = explode('/', $path);
        $dirName = $parts[0];
        $fileName = implode('/', array_splice($parts, 1));

        return [$dirName, str_replace('.', '/', $fileName)];
    }

    /**
     * Check configuration in Config file
     *
     * @param string $code
     * @return array|null
     * @throws \Igniter\Flame\Exception\SystemException
     */
    protected function validateMetaFile($config, $code)
    {
        foreach ([
            'code',
            'name',
            'description',
            'author',
        ] as $item) {
            if (!array_key_exists($item, $config)) {
                throw new SystemException(sprintf(
                    Lang::get('igniter::system.missing.config_key'),
                    $item, $code
                ));
            }

            if ($item == 'code' && $code !== $config[$item]) {
                throw new SystemException(sprintf(
                    Lang::get('igniter::system.missing.config_code_mismatch'),
                    $config[$item], $code
                ));
            }
        }

        return $config;
    }

    protected function writeChildThemeMetaFile($path, $parentTheme, $themeConfig)
    {
        $themeConfig['parent'] = $parentTheme->name;
        $themeConfig['version'] = array_get($parentTheme->config, 'version');
        $themeConfig['author'] = array_get($parentTheme->config, 'author', '');
        $themeConfig['homepage'] = array_get($parentTheme->config, 'homepage', '');
        $themeConfig['require'] = $parentTheme->requires;

        if (File::isDirectory($path)) {
            throw new SystemException('Child theme path already exists.');
        }

        File::makeDirectory($path, 0777, false, true);

        File::put($path.'/theme.json', json_encode($themeConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
