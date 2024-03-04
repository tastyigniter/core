<?php

namespace Igniter\Flame\Flash;

class Message implements \ArrayAccess
{
    /** The title of the message. */
    public ?string $title = null;

    /** The body of the message. */
    public ?string $message = null;

    /** The message level. */
    public string $level = 'info';

    /** Whether the message should auto-hide. */
    public bool $important = false;

    /** Whether the message is an overlay. */
    public bool $overlay = false;

    /**
     * Create a new message instance.
     */
    public function __construct(array $attributes = [])
    {
        $this->update($attributes);
    }

    /**
     * Update the attributes.
     */
    public function update(array $attributes = []): self
    {
        $attributes = array_filter($attributes);

        foreach ($attributes as $key => $attribute) {
            $this->$key = $attribute;
        }

        return $this;
    }

    /**
     * Whether the given offset exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }

    /**
     * Fetch the offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset;
    }

    /**
     * Assign the offset.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    /**
     * Unset the offset.
     */
    public function offsetUnset(mixed $offset): void
    {
        //
    }

    public function toArray()
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'level' => $this->level,
            'important' => $this->important,
            'overlay' => $this->overlay,
        ];
    }
}
