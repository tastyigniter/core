<?php

namespace Igniter\Flame\Pagic\Source;

interface SourceResolverInterface
{
    /**
     * Get a source instance.
     */
    public function source(?string $name = null): SourceInterface;

    /**
     * Get the default source name.
     */
    public function getDefaultSourceName(): string;

    /**
     * Set the default source name.
     */
    public function setDefaultSourceName(string $name);
}
