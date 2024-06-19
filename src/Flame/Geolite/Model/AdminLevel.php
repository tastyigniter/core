<?php

namespace Igniter\Flame\Geolite\Model;

readonly class AdminLevel
{
    public function __construct(
        private int $level,
        private string $name,
        private ?string $code = null
    ) {}

    /**
     * Returns the administrative level.
     *
     * @return int Level number [1,5]
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Returns the administrative level name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the administrative level short name.
     *
     * @return string|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Returns a string with the administrative level name.
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
