<?php

namespace Igniter\Flame\Pagic\Source;

class SourceResolver implements SourceResolverInterface
{
    /**
     * All of the registered sources.
     */
    protected array $sources = [];

    /**
     * The default source name.
     */
    protected ?string $default = null;

    /**
     * Create a new source resolver instance.
     */
    public function __construct(array $sources = [])
    {
        foreach ($sources as $name => $source) {
            $this->addSource($name, $source);
        }
    }

    /**
     * Get a source instance.
     */
    public function source(?string $name = null): SourceInterface
    {
        if (is_null($name)) {
            $name = $this->getDefaultSourceName();
        }

        return $this->sources[$name];
    }

    /**
     * Add a source to the resolver.
     */
    public function addSource(string $name, SourceInterface $source)
    {
        $this->sources[$name] = $source;
    }

    /**
     * Check if a source has been registered.
     */
    public function hasSource(string $name): bool
    {
        return isset($this->sources[$name]);
    }

    /**
     * Get the default source name.
     */
    public function getDefaultSourceName(): string
    {
        return $this->default;
    }

    /**
     * Set the default source name.
     */
    public function setDefaultSourceName(string $name)
    {
        $this->default = $name;
    }
}
