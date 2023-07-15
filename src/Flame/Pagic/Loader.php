<?php

namespace Igniter\Flame\Pagic;

use Exception;
use Igniter\Flame\Pagic\Contracts\TemplateInterface;
use Igniter\Flame\Pagic\Contracts\TemplateLoader;
use Igniter\Flame\Support\Facades\File;
use Igniter\Main\Template\Partial as PartialTemplate;
use Illuminate\Support\Facades\App;

/**
 * Loader class
 */
class Loader implements TemplateLoader
{
    protected string $extension = 'php';

    protected array $fallbackCache = [];

    protected array $cache = [];

    protected ?TemplateInterface $source;

    /**
     * Sets a object to load the template from.
     */
    public function setSource(TemplateInterface $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): TemplateInterface
    {
        return $this->source;
    }

    /**
     * Gets the markup section of a template, given its name.
     * @throws Exception When $name is not found
     */
    public function getMarkup(string $name): ?string
    {
        if ($this->validateTemplateSource($name)) {
            return $this->source->getMarkup();
        }

        return $this->getContents($name);
    }

    public function getContents(string $name): ?string
    {
        if ($this->validateTemplateSource($name)) {
            return $this->source->getContent();
        }

        return File::get($this->findTemplate($name));
    }

    public function getFilename(string $name): ?string
    {
        if ($this->validateTemplateSource($name)) {
            return $this->source->getFilePath();
        }

        return $this->findTemplate($name);
    }

    /**
     * Gets the path of a view file
     */
    protected function findTemplate(string $name): string
    {
        $finder = App::make('view')->getFinder();

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        if (File::isFile($name)) {
            return $this->cache[$name] = $name;
        }

        $view = $name;
        if (File::extension($view) == $this->extension) {
            $view = substr($view, 0, -strlen($this->extension));
        }

        $path = $finder->find($view);

        return $this->cache[$name] = $path;
    }

    public function getCacheKey(string $name): string
    {
        if ($this->validateTemplateSource($name)) {
            return $this->source->getTemplateCacheKey();
        }

        return $this->findTemplate($name);
    }

    public function isFresh(string $name, int $time): bool
    {
        if ($this->validateTemplateSource($name)) {
            return $this->source->mTime <= $time;
        }

        return File::lastModified($this->findTemplate($name)) <= $time;
    }

    public function exists(string $name): bool
    {
        if ($this->validateTemplateSource($name)) {
            return $this->source->exists;
        }

        try {
            $this->findTemplate($name);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function getFilePath(): ?string
    {
        return $this->getSource()->getFilePath();
    }

    /**
     * Internal method that checks if the template name matches
     * the loaded object, with fallback support to partials.
     */
    protected function validateTemplateSource(string $name): bool
    {
        if ($name == $this->source->getFilePath()) {
            return true;
        }

        if ($fallbackObj = $this->findFallbackObject($name)) {
            $this->source = $fallbackObj;

            return true;
        }

        return false;
    }

    /**
     * Looks up a fallback partial object.
     */
    protected function findFallbackObject(string $name): PartialTemplate|bool
    {
        if (str_contains($name, '::')) {
            return false;
        }

        if (array_key_exists($name, $this->fallbackCache)) {
            return $this->fallbackCache[$name];
        }

        return $this->fallbackCache[$name] = PartialTemplate::find($name);
    }
}
