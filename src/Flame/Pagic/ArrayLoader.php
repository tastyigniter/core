<?php

namespace Igniter\Flame\Pagic;

use Exception;
use InvalidArgumentException;

/**
 * Loads a template from an array.
 * @method \Igniter\Flame\Pagic\Contracts\TemplateInterface getSource()
 */
class ArrayLoader extends Loader
{
    /**
     * @param array $templates An array of templates (keys are the names, and values are the source code)
     */
    public function __construct(protected array $templates = []) {}

    /**
     * Adds or overrides a template.
     *
     * @param string $name The template name
     * @param string $template The template source
     */
    public function setTemplate(string $name, string $template)
    {
        $this->templates[$name] = $template;
    }

    public function getFilename($name): ?string
    {
        return $name;
    }

    /**
     * Gets the markup section of a template, given its name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The template source code
     *
     * @throws Exception When $name is not found
     */
    public function getMarkup(string $name): string
    {
        return array_get($this->templates, $name);
    }

    /**
     * Gets the source code of a template, given its name.
     *
     * @param string $name The name of the template to load
     *
     * @return string The template source code
     *
     * @throws Exception When $name is not found
     */
    public function getContents(string $name): string
    {
        return array_get($this->templates, $name);
    }

    public function exists(string $name): bool
    {
        return isset($this->templates[$name]);
    }

    public function getCacheKey(string $name): string
    {
        if (!isset($this->templates[$name])) {
            throw new InvalidArgumentException(sprintf('Template "%s" is not defined.', $name));
        }

        return $name.':'.$this->templates[$name];
    }

    public function isFresh(string $name, int $time): bool
    {
        if (!isset($this->templates[$name])) {
            throw new InvalidArgumentException(sprintf('Template "%s" is not defined.', $name));
        }

        return true;
    }

    public function getFilePath(): ?string
    {
        return null;
    }
}
