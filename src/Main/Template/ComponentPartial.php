<?php

namespace Igniter\Main\Template;

use Igniter\Flame\Pagic\Contracts\TemplateInterface;
use Igniter\Flame\Support\Extendable;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\BaseComponent;

class ComponentPartial extends Extendable implements TemplateInterface
{
    /**
     * @var string The component partial file name.
     */
    public $fileName;

    /**
     * @var string Last modified time.
     */
    public $mTime;

    /**
     * @var string Partial content.
     */
    public $content;

    /**
     * @var array Allowable file extensions.
     */
    protected $allowedExtensions = ['blade.php', 'php'];

    /**
     * @var string Default file extension.
     */
    protected $defaultExtension = 'blade.php';

    /**
     * @var int The maximum allowed path nesting level. The default value is 2,
     * meaning that files can only exist in the root directory, or in a
     * subdirectory. Set to null if any level is allowed.
     */
    protected $maxNesting = 2;

    /**
     * Creates an instance of the object and associates it with a component.
     */
    public function __construct(protected string $componentPath)
    {
        $this->extendableConstruct();
    }

    public static function load(string $source, string $fileName): mixed
    {
        return (new static($source))->find($fileName);
    }

    public static function loadCached(string $source, string $fileName): mixed
    {
        return static::load($source, $fileName);
    }

    /**
     * @param \Igniter\Main\Classes\Theme $theme
     * @param \Igniter\System\Classes\BaseComponent $component
     * @param string $fileName
     * @return mixed
     */
    public static function loadOverrideCached($theme, $componentName, $fileName)
    {
        $partial = Partial::loadCached($theme->getName(), $componentName.'/'.$fileName);

        if ($partial === null) {
            $partial = Partial::loadCached($theme->getName(), strtolower($componentName).'/'.$fileName);
        }

        return $partial;
    }

    /**
     * Find a single template by its file name.
     *
     * @param string $fileName
     *
     * @return mixed|static
     */
    public function find($fileName)
    {
        $filePath = $this->getFilePath($fileName);

        if (!File::isFile($filePath)) {
            return null;
        }

        if (($content = @File::get($filePath)) === false) {
            return null;
        }

        $this->fileName = File::basename($filePath);
        $this->mTime = File::lastModified($filePath);
        $this->content = $content;

        return $this;
    }

    /**
     * Returns true if the specific component contains a matching partial.
     *
     * @param BaseComponent $component Specifies a component the file belongs to.
     * @param string $fileName Specifies the file name to check.
     *
     * @return bool
     */
    public static function check(BaseComponent $component, $fileName)
    {
        $partial = new static($component);
        $filePath = $partial->getFilePath($fileName);
        if (File::extension($filePath) === '') {
            $filePath .= '.'.$partial->getDefaultExtension();
        }

        return File::isFile($filePath);
    }

    /**
     * Returns the key used by the Template cache.
     */
    public function getTemplateCacheKey(): string
    {
        return $this->getFilePath();
    }

    /**
     * Returns the default extension used by this template.
     * @return string
     */
    public function getDefaultExtension()
    {
        return $this->defaultExtension;
    }

    /**
     * Returns the absolute file path.
     *
     * @param string $fileName Specifies the file name to return the path to.
     */
    public function getFilePath(string $fileName = null): string
    {
        if ($fileName === null) {
            $fileName = $this->fileName;
        }

        if (File::isPathSymbol($this->componentPath)) {
            $this->componentPath = File::symbolizePath($this->componentPath);
        }

        $basename = $fileName;
        if (!strlen(File::extension($basename))) {
            $basename .= '.'.$this->defaultExtension;
        }

        if (File::isFile($path = $this->componentPath.'/'.$basename)) {
            return $path;
        }

        // Check the shared "/partials" directory for the partial
        $sharedPath = dirname($this->componentPath, 2).'/_partials/'.$basename;
        if (File::isFile($sharedPath)) {
            return $sharedPath;
        }

        return $this->componentPath.'/'.$fileName;
    }

    /**
     * Returns the file name.
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Returns the file name without the extension.
     */
    public function getBaseFileName(): string
    {
        $pos = strrpos($this->fileName, '.');
        if ($pos === false) {
            return $this->fileName;
        }

        return substr($this->fileName, 0, $pos);
    }

    /**
     * Returns the file content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Gets the markup section of a template
     * @return string The template source code
     */
    public function getMarkup(): string
    {
        return $this->content.PHP_EOL;
    }

    /**
     * Gets the code section of a template
     */
    public function getCode(): string
    {
        return 'missing-code';
    }
}
