<?php

declare(strict_types=1);

namespace Igniter\Main\Classes;

use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Pagic\Model;
use Igniter\Flame\Pagic\Source\ChainFileSource;
use Igniter\Flame\Pagic\Source\FileSource;
use Igniter\Flame\Pagic\Source\SourceInterface;
use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Main\Events\ThemeExtendFormConfigEvent;
use Igniter\Main\Events\ThemeGetActiveEvent;
use Igniter\Main\Models\Theme as ThemeModel;
use Igniter\Main\Template\Content as ContentTemplate;
use Igniter\Main\Template\File as FileTemplate;
use Igniter\Main\Template\Layout as LayoutTemplate;
use Igniter\Main\Template\Page as PageTemplate;
use Igniter\Main\Template\Partial as PartialTemplate;
use Illuminate\Support\Collection;
use RuntimeException;

class Theme
{
    /** The theme name */
    public ?string $name = null;

    /** Theme label. */
    public ?string $label = null;

    /** Specifies a description to accompany the theme */
    public ?string $description = null;

    /** The theme author */
    public ?string $author = null;

    /** The parent theme code */
    public ?string $parentName = null;

    /** List of extension code and version required by this theme */
    public array $requires = [];

    /** The theme relative path to the templates files */
    public ?string $sourcePath = null;

    /** The theme relative path to the assets directory */
    public ?string $assetPath = null;

    /** The theme relative path to the meta directory */
    public ?string $metaPath = null;

    /** The theme path relative to base path */
    public ?string $publicPath;

    /** Determine if this theme is active. */
    public bool $active = false;

    public bool $locked = false;

    /** When false, the admin cannot create a child theme from this theme. */
    public bool $child = true;

    /** Path to the screenshot image, relative to this theme folder. */
    public ?string $screenshot = null;

    /** Cached theme configuration. */
    protected ?array $configCache = null;

    protected ?array $customData = null;

    protected ?SourceInterface $fileSource = null;

    protected ?array $formConfigCache = null;

    protected ?array $builderConfigCache = null;

    protected ?string $screenshotData = null;

    protected ?self $parentTheme = null;

    protected static array $allowedTemplateModels = [
        '_layouts' => LayoutTemplate::class,
        '_pages' => PageTemplate::class,
        '_partials' => PartialTemplate::class,
        '_content' => ContentTemplate::class,
        '_files' => FileTemplate::class,
    ];

    public function __construct(public string $path, public array $config = [])
    {
        $this->path = realpath($path) ?: $path;
        $this->publicPath = File::localToPublic($path);
        $this->fillFromConfig();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSourcePath(): string
    {
        return $this->path.$this->sourcePath;
    }

    public function getMetaPath(): string
    {
        if (is_null($this->metaPath)) {
            foreach (['/_meta', '/meta', $this->sourcePath.'/_meta', $this->sourcePath.'/meta'] as $metaPath) {
                if (File::isDirectory($this->path.$metaPath)) {
                    $this->metaPath = $metaPath;
                }
            }
        }

        return $this->path.$this->metaPath;
    }

    public function getAssetsFilePath(): string
    {
        return $this->getMetaPath().'/assets.json';
    }

    public function getAssetPath(): string
    {
        return $this->path.$this->assetPath;
    }

    public function getPathsToPublish(): array
    {
        $publishPath = $this->config['publish-paths'] ?? [];

        if (!$publishPath && File::exists($this->getAssetPath())) {
            return [$this->getAssetPath() => public_path('vendor/'.$this->name)];
        }

        $result = [];
        foreach ($publishPath as $path) {
            if (File::isDirectory($this->path.$path)) {
                $result[$this->path.$path] = public_path('vendor/'.$this->name);
            }
        }

        return $result;
    }

    public function getDirName(): string
    {
        return basename($this->path);
    }

    public function getParentPath(): ?string
    {
        return optional($this->getParent())->getPath();
    }

    public function getParentName(): ?string
    {
        return $this->parentName;
    }

    public function getParent(): ?self
    {
        if (!is_null($this->parentTheme)) {
            return $this->parentTheme;
        }

        return $this->parentTheme = resolve(ThemeManager::class)->findTheme($this->getParentName());
    }

    public function hasParent(): bool
    {
        return !is_null($this->parentName) && !is_null($this->getParent());
    }

    public function requires($require): self
    {
        if (!is_array($require)) {
            $require = [$require];
        }

        $this->requires = $require;

        return $this;
    }

    public function screenshot(string $name): self
    {
        foreach (array_keys($this->getFindInPaths()) as $findInPath) {
            foreach (array_keys(ThemeModel::ICON_MIMETYPES) as $extension) {
                if (File::isFile($findInPath.'/'.$name.'.'.$extension)) {
                    $this->screenshot = $findInPath.'/'.$name.'.'.$extension;
                    break 2;
                }
            }
        }

        return $this;
    }

    public function getScreenshotData(): string
    {
        if (!is_null($this->screenshotData)) {
            return $this->screenshotData;
        }

        if (is_null($this->screenshot)) {
            $this->screenshot('screenshot');
        }

        $screenshotData = '';
        if (File::exists($file = $this->screenshot)) {
            $extension = pathinfo((string)$file, PATHINFO_EXTENSION);
            if (!array_key_exists($extension, ThemeModel::ICON_MIMETYPES)) {
                throw new FlashException('Invalid theme icon file type in: '.$this->name.'. Only SVG and PNG images are supported');
            }

            $mimeType = ThemeModel::ICON_MIMETYPES[$extension];
            $data = base64_encode(File::get($file));

            $screenshotData = sprintf('data:%s;base64,%s', $mimeType, $data);
        }

        return $this->screenshotData = $screenshotData;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function loadThemeFile(): void
    {
        if (File::exists($path = $this->getPath().'/theme.php')) {
            require $path;
        }

        if ($this->getParentPath() && File::exists($path = $this->getParentPath().'/theme.php')) {
            require $path;
        }
    }

    public static function getActiveCode(): ?string
    {
        /** @var string $apiResult */
        if (!is_null($apiResult = ThemeGetActiveEvent::dispatchOnce())) {
            return $apiResult;
        }

        if (Igniter::hasDatabase() && $activeTheme = ThemeModel::getDefault()) {
            return $activeTheme->code;
        }

        return config('igniter-system.defaultTheme', '');
    }

    //
    //
    //

    public function getConfig(): array
    {
        if (!is_null($this->configCache)) {
            return $this->configCache;
        }

        $configCache = [];
        foreach (array_filter([$this->getParent(), $this]) as $theme) {
            $formConfigFile = $theme->getMetaPath().'/fields.php';
            $config = File::exists($formConfigFile) ? File::getRequire($formConfigFile) : [];

            foreach (array_get($config, 'form', []) as $key => $definitions) {
                foreach ($definitions as $index => $definition) {
                    if (!is_array($definition)) {
                        $configCache['form'][$key][$index] = $definition;
                    } else {
                        foreach ($definition as $fieldIndex => $field) {
                            $configCache['form'][$key][$index][$fieldIndex] = $field;
                        }
                    }
                }
            }
        }

        return $this->configCache = $configCache;
    }

    public function getFormConfig(): array
    {
        if (!is_null($this->formConfigCache)) {
            return $this->formConfigCache;
        }

        $config = $this->getConfigValue('form', []);

        event($event = new ThemeExtendFormConfigEvent($this->getName(), $config));

        return $this->formConfigCache = $event->getConfig();
    }

    public function getConfigValue(string $name, mixed $default = null): mixed
    {
        return array_get($this->getConfig(), $name, $default);
    }

    /**
     * Returns the page builder configuration from the theme meta `builder.php` file,
     * merged over the parent theme configuration. Pages are merged by page code.
     */
    public function getBuilderConfig(): array
    {
        if (!is_null($this->builderConfigCache)) {
            return $this->builderConfigCache;
        }

        $builderConfig = ['pages' => []];
        foreach (array_filter([$this->getParent(), $this]) as $theme) {
            $configFile = $theme->getMetaPath().'/builder.php';
            $config = File::exists($configFile) ? (array)File::getRequire($configFile) : [];

            $builderConfig['pages'] = array_merge($builderConfig['pages'], (array)array_get($config, 'pages', []));
        }

        return $this->builderConfigCache = $builderConfig;
    }

    /**
     * Returns the pages that can be edited with the page builder, keyed by page code.
     *
     * @return array<string, array{label: string, required: list<string>, default: array}>
     */
    public function getBuildablePages(): array
    {
        $pages = [];
        foreach ($this->getBuilderConfig()['pages'] as $code => $page) {
            $pages[$code] = [
                'label' => (string)array_get($page, 'label', $code),
                'required' => array_values((array)array_get($page, 'required', [])),
                'default' => (array)array_get($page, 'default', []),
            ];
        }

        return $pages;
    }

    /**
     * @return array{label: string, required: list<string>, default: array}|null
     */
    public function getBuildablePage(string $pageCode): ?array
    {
        return $this->getBuildablePages()[$pageCode] ?? null;
    }

    public function hasFormConfig(): bool
    {
        return $this->hasParent() ? $this->getParent()->hasFormConfig() : !empty($this->getFormConfig());
    }

    public function hasCustomData(): bool
    {
        return !empty($this->getCustomData());
    }

    public function getCustomData(): array
    {
        if (!is_null($this->customData)) {
            return $this->customData;
        }

        $themeData = ThemeModel::forTheme($this)->getThemeData();

        $customData = [];
        foreach ($this->getFormConfig() as $item) {
            foreach (array_get($item, 'fields', []) as $name => $field) {
                $customData[$name] = array_get($themeData, $name, array_get($field, 'default'));
            }
        }

        return $this->customData = array_undot($customData);
    }

    /**
     * Returns variables that should be passed to the asset combiner.
     */
    public function getAssetVariables(): array
    {
        $result = [];

        if (!ThemeModel::forTheme($this)->getThemeData()) {
            return $result;
        }

        $formFields = ThemeModel::forTheme($this)->getFieldsConfig();
        foreach ($formFields as $attribute => $field) {
            if (!$varNames = array_get($field, 'assetVar')) {
                continue;
            }

            if (!is_array($varNames)) {
                $varNames = [$varNames];
            }

            foreach ($varNames as $varName) {
                $result[$varName] = $this->{$attribute};
            }
        }

        return $result;
    }

    public function fillFromConfig(): void
    {
        if (isset($this->config['code'])) {
            $this->name = $this->config['code'];
        }

        if (isset($this->config['name'])) {
            $this->label = $this->config['name'];
        }

        if (isset($this->config['parent'])) {
            $this->parentName = $this->config['parent'];
        }

        if (isset($this->config['description'])) {
            $this->description = $this->config['description'];
        }

        if (isset($this->config['author'])) {
            $this->author = $this->config['author'];
        }

        if (isset($this->config['require'])) {
            $this->requires($this->config['require']);
        }

        if (!$this->sourcePath) {
            $this->sourcePath = $this->config['source-path'] ?? '';
        }

        if (!$this->assetPath) {
            $this->assetPath = $this->config['asset-path'] ?? '/assets';
        }

        if (!$this->metaPath) {
            $this->metaPath = $this->config['meta-path'] ?? null;
        }

        if (array_key_exists('locked', $this->config)) {
            $this->locked = (bool)$this->config['locked'];
        }

        if (array_key_exists('child', $this->config)) {
            $this->child = (bool)$this->config['child'];
        }
    }

    /**
     * Translation namespace for this theme. Defaults to the theme code with dashes swapped for dots.
     */
    public function langNamespace(): string
    {
        $namespace = $this->config['lang'] ?? null;

        return is_string($namespace) && $namespace !== ''
            ? $namespace
            : str_replace('-', '.', (string)$this->name);
    }

    protected function getFindInPaths(): array
    {
        $findInPaths = [];
        $findInPaths[$this->path] = $this->publicPath;
        if (!is_null($parent = $this->getParent())) {
            $findInPaths[$parent->path] = $parent->publicPath;
        }

        return $findInPaths;
    }

    //
    //
    //

    public function listPages(): Collection
    {
        return PageTemplate::listInTheme($this->getName());
    }

    public function listPartials(): Collection
    {
        return PartialTemplate::listInTheme($this->getName());
    }

    public function listLayouts(): Collection
    {
        return LayoutTemplate::listInTheme($this->getName());
    }

    public function listRequires(): array
    {
        return array_merge($this->hasParent() ? $this->getParent()->listRequires() : [], $this->requires);
    }

    //
    //
    //

    public function makeFileSource(): SourceInterface
    {
        if (!is_null($this->fileSource)) {
            return $this->fileSource;
        }

        if ($this->hasParent() && $parent = $this->getParent()) {
            $source = new ChainFileSource([
                new FileSource($this->getSourcePath()),
                new FileSource($parent->getSourcePath()),
            ]);
        } else {
            $source = new FileSource($this->getSourcePath());
        }

        return $this->fileSource = $source;
    }

    public function onTemplate(string $dirName): Model
    {
        $modelClass = $this->getTemplateClass($dirName);

        return $modelClass::on($this->getName());
    }

    public function newTemplate(string $dirName): Model
    {
        $class = $this->getTemplateClass($dirName);

        return new $class;
    }

    public function getTemplateClass(string $dirName): string
    {
        if (!isset(self::$allowedTemplateModels[$dirName])) {
            throw new RuntimeException(sprintf('Source Model not found for [%s].', $dirName));
        }

        return self::$allowedTemplateModels[$dirName];
    }

    /**
     * Implements the getter functionality.
     */
    public function __get(string $name): mixed
    {
        return array_get($this->getCustomData(), $name);
    }

    /**
     * Determine if an attribute exists on the object.
     */
    public function __isset(string $key): bool
    {
        if ($this->hasCustomData()) {
            return array_has($this->getCustomData(), $key);
        }

        return false;
    }
}
