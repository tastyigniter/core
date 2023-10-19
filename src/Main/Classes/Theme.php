<?php

namespace Igniter\Main\Classes;

use Exception;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Igniter;
use Igniter\Flame\Pagic\Source\ChainFileSource;
use Igniter\Flame\Pagic\Source\FileSource;
use Igniter\Flame\Pagic\Source\SourceInterface;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Events\ThemeExtendFormConfigEvent;
use Igniter\Main\Events\ThemeGetActiveEvent;
use Igniter\Main\Models\Theme as ThemeModel;
use Igniter\Main\Template\Content as ContentTemplate;
use Igniter\Main\Template\Layout as LayoutTemplate;
use Igniter\Main\Template\Page as PageTemplate;
use Igniter\Main\Template\Partial as PartialTemplate;
use Igniter\System\Helpers\SystemHelper;

class Theme
{
    /**
     * @var string The theme name
     */
    public $name;

    /**
     * @var string Theme label.
     */
    public $label;

    /**
     * @var string Specifies a description to accompany the theme
     */
    public $description;

    /**
     * @var string The theme author
     */
    public $author;

    /**
     * @var string The parent theme code
     */
    public $parentName;

    /**
     * @var string List of extension code and version required by this theme
     */
    public $requires = [];

    /**
     * @var string The theme path absolute base path
     */
    public $path;

    /**
     * @var string The theme relative path to the templates files
     */
    public $sourcePath;

    /**
     * @var string The theme relative path to the assets directory
     */
    public $assetPath;

    /**
     * @var string The theme relative path to the form fields file
     */
    public $formConfigFile;

    /**
     * @var string The theme relative path to the assets config file
     */
    public $assetConfigFile;

    /**
     * @var string The theme path relative to base path
     */
    public $publicPath;

    /**
     * @var bool Determine if this theme is active (false) or not (true).
     */
    public $active;

    /**
     * @var string The theme author
     */
    public $locked;

    /**
     * @var string Path to the screenshot image, relative to this theme folder.
     */
    public $screenshot;

    public $config = [];

    /**
     * @var array Cached theme configuration.
     */
    protected $configCache;

    protected $customData;

    protected $fileSource;

    protected $formConfigCache;

    protected $screenshotData;

    protected $parentTheme;

    protected static $allowedTemplateModels = [
        '_layouts' => LayoutTemplate::class,
        '_pages' => PageTemplate::class,
        '_partials' => PartialTemplate::class,
        '_content' => ContentTemplate::class,
    ];

    public function __construct($path, array $config = [])
    {
        $this->path = realpath($path);
        $this->publicPath = File::localToPublic($path);
        $this->config = $config;
        $this->fillFromConfig();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getSourcePath()
    {
        return $this->path.$this->sourcePath;
    }

    /**
     * @return string
     */
    public function getAssetPath()
    {
        return $this->path.$this->assetPath;
    }

    /**
     * @return string
     */
    public function getAssetConfigFile()
    {
        return $this->path.$this->assetPath;
    }

    /**
     * @return array
     */
    public function getPathsToPublish()
    {
        $result = [];
        foreach ($this->config['publish-paths'] ?? [] as $path) {
            if (File::isDirectory($this->path.$path)) {
                $result[$this->path.$path] = public_path('vendor/'.$this->name);
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getDirName()
    {
        return basename($this->path);
    }

    public function getParentPath()
    {
        return optional($this->getParent())->getPath();
    }

    public function getParentName()
    {
        return $this->parentName;
    }

    public function getParent()
    {
        if (!is_null($this->parentTheme)) {
            return $this->parentTheme;
        }

        return $this->parentTheme = resolve(ThemeManager::class)->findTheme($this->getParentName());
    }

    public function hasParent()
    {
        return !is_null($this->parentName) && !is_null($this->getParent());
    }

    public function requires($require)
    {
        if (!is_array($require)) {
            $require = [$require];
        }

        $this->requires = $require;

        return $this;
    }

    public function screenshot($name)
    {
        foreach ($this->getFindInPaths() as $findInPath => $publicPath) {
            foreach (ThemeModel::ICON_MIMETYPES as $extension => $mimeType) {
                if (File::isFile($findInPath.'/'.$name.'.'.$extension)) {
                    $this->screenshot = $findInPath.'/'.$name.'.'.$extension;
                    break 2;
                }
            }
        }

        return $this;
    }

    public function getScreenshotData()
    {
        if (!is_null($this->screenshotData)) {
            return $this->screenshotData;
        }

        if (is_null($this->screenshot)) {
            $this->screenshot('screenshot');
        }

        $screenshotData = '';
        if (file_exists($file = $this->screenshot)) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (!array_key_exists($extension, ThemeModel::ICON_MIMETYPES)) {
                throw FlashException::error('Invalid theme icon file type in: '.$this->name.'. Only SVG and PNG images are supported');
            }

            $mimeType = ThemeModel::ICON_MIMETYPES[$extension];
            $data = base64_encode(file_get_contents($file));

            $screenshotData = "data:{$mimeType};base64,{$data}";
        }

        return $this->screenshotData = $screenshotData;
    }

    public function isActive()
    {
        return $this->active;
    }

    public function loadThemeFile()
    {
        if (File::exists($path = $this->getPath().'/theme.php')) {
            require $path;
        }

        if (File::exists($path = $this->getParentPath().'/theme.php')) {
            require $path;
        }
    }

    public static function getActiveCode(): ?string
    {
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

    public function getConfig()
    {
        if (!is_null($this->configCache)) {
            return $this->configCache;
        }

        $configCache = [];
        $paths[] = $this->getSourcePath();
        if ($parent = $this->getParent()) {
            $paths[] = $parent->getSourcePath();
        }

        foreach (array_reverse($paths) as $findInPath) {
            $config = File::exists($path = $findInPath.$this->formConfigFile)
                ? File::getRequire($path) : [];

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

    public function getFormConfig()
    {
        if (!is_null($this->formConfigCache)) {
            return $this->formConfigCache;
        }

        $config = $this->getConfigValue('form', []);

        event($event = new ThemeExtendFormConfigEvent($this->getName(), $config));

        return $this->formConfigCache = $event->getConfig();
    }

    public function getConfigValue($name, $default = null)
    {
        return array_get($this->getConfig(), $name, $default);
    }

    public function hasFormConfig()
    {
        return $this->hasParent() ? $this->getParent()->hasFormConfig() : !empty($this->getFormConfig());
    }

    public function hasCustomData()
    {
        return !empty($this->getCustomData());
    }

    public function getCustomData()
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
     * @return array
     */
    public function getAssetVariables()
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

    public function fillFromConfig()
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

        if (!$this->formConfigFile) {
            $this->formConfigFile = $this->config['form-config-file'] ?? '/_meta/fields.php';
        }

        if (!$this->assetConfigFile) {
            $this->assetConfigFile = $this->config['assets-config-file'] ?? '/_meta/assets.json';
        }

        if (array_key_exists('locked', $this->config)) {
            $this->locked = (bool)$this->config['locked'];
        }
    }

    //
    //
    //

    public function listPages()
    {
        return PageTemplate::listInTheme($this->getName());
    }

    public function listPartials()
    {
        return PartialTemplate::listInTheme($this->getName());
    }

    public function listLayouts()
    {
        return LayoutTemplate::listInTheme($this->getName());
    }

    public function getPagesOptions()
    {
    }

    public function listRequires()
    {
        return SystemHelper::parsePackageCodes($this->requires);
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

    /**
     * @return \Igniter\Flame\Pagic\Model|\Igniter\Flame\Pagic\Finder
     */
    public function onTemplate($dirName)
    {
        $modelClass = $this->getTemplateClass($dirName);

        return $modelClass::on($this->getName());
    }

    /**
     * @return \Igniter\Flame\Pagic\Model
     */
    public function newTemplate($dirName)
    {
        $class = $this->getTemplateClass($dirName);

        return new $class;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getTemplateClass($dirName)
    {
        if (!isset(self::$allowedTemplateModels[$dirName])) {
            throw new Exception(sprintf('Source Model not found for [%s].', $dirName));
        }

        return self::$allowedTemplateModels[$dirName];
    }

    /**
     * Implements the getter functionality.
     *
     * @param string $name
     *
     * @return void
     */
    public function __get($name)
    {
        return array_get($this->getCustomData(), $name);
    }

    /**
     * Determine if an attribute exists on the object.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        if ($this->hasCustomData()) {
            return array_has($this->getCustomData(), $key);
        }

        return false;
    }

    protected function getFindInPaths()
    {
        $findInPaths = [];
        $findInPaths[$this->path] = $this->publicPath;
        if ($parent = $this->getParent()) {
            $findInPaths[$parent->path] = $parent->publicPath;
        }

        return $findInPaths;
    }
}
