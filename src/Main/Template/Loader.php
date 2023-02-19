<?php

namespace Igniter\Main\Template;

use Igniter\Main\Template\Partial as PartialTemplate;

/**
 * This class implements a template loader for the main app.
 */
class Loader extends \Igniter\Flame\Pagic\Loader
{
    public function getMarkup($name)
    {
        if (!$this->validateTemplateSource($name))
            return parent::getContents($name);

        return $this->source->getMarkup();
    }

    public function getContents($name)
    {
        if (!$this->validateTemplateSource($name))
            return parent::getContents($name);

        return $this->source->getContent();
    }

    public function getFilename($name)
    {
        if (!$this->validateTemplateSource($name))
            return parent::getFilename($name);

        return $this->source->getFilePath();
    }

    public function getCacheKey($name)
    {
        if (!$this->validateTemplateSource($name))
            return parent::getCacheKey($name);

        return $this->source->getTemplateCacheKey();
    }

    public function isFresh($name, $time)
    {
        if (!$this->validateTemplateSource($name))
            return parent::isFresh($name, $time);

        return $this->source->mTime <= $time;
    }

    public function exists($name)
    {
        return $this->source->exists;
    }

    /**
     * Internal method that checks if the template name matches
     * the loaded object, with fallback support to partials.
     *
     * @param $name
     *
     * @return bool
     */
    protected function validateTemplateSource($name)
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
     *
     * @param $name
     *
     * @return bool|\Igniter\Main\Template\Partial
     */
    protected function findFallbackObject($name)
    {
        if (strpos($name, '::') !== false)
            return false;

        if (array_key_exists($name, $this->fallbackCache))
            return $this->fallbackCache[$name];

        return $this->fallbackCache[$name] = PartialTemplate::find($name);
    }
}
