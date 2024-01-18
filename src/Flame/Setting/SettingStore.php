<?php

namespace Igniter\Flame\Setting;

use Illuminate\Support\Arr;

abstract class SettingStore
{
    /** The settings items. */
    protected array $items = [];

    /** Whether the store has changed since it was last loaded. */
    protected bool $unsaved = false;

    /** Whether the settings data are loaded. */
    protected bool $loaded = false;

    /** Get a specific key from the settings data. */
    public function get(array|string $key, mixed $default = null): mixed
    {
        $this->load();

        return Arr::get($this->items, $key, $default);
    }

    /** Determine if a key exists in the settings data. */
    public function has(string $key): bool
    {
        $this->load();

        return Arr::has($this->items, $key);
    }

    /** Set a specific key to a value in the settings data. */
    public function set(array|string $key, mixed $value = null): self
    {
        $this->load();
        $this->unsaved = true;

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                Arr::set($this->items, $k, $v);
            }
        } else {
            Arr::set($this->items, $key, $value);
        }

        return $this;
    }

    /** Unset a key in the settings data. */
    public function forget(string $key)
    {
        $this->unsaved = true;

        if ($this->has($key)) {
            Arr::forget($this->items, $key);
        }
    }

    /** Unset all keys in the settings data. */
    public function forgetAll()
    {
        $this->unsaved = true;
        $this->items = [];
    }

    /** Get all settings data. */
    public function all(): array
    {
        $this->load();

        return $this->items;
    }

    /** Save any changes done to the settings data. */
    public function save()
    {
        if (!$this->unsaved) {
            // either nothing has been changed, or data has not been loaded, so
            // do nothing by returning early
            return;
        }

        $this->write($this->items);
        $this->unsaved = false;
    }

    /** Make sure data is loaded. */
    public function load(bool $force = false)
    {
        if (!$this->loaded || $force) {
            $this->items = $this->read();
            $this->loaded = true;
        }
    }

    /** Read the data from the store. */
    abstract protected function read(): array;

    /**
     * Write the data into the store.
     */
    abstract protected function write(array $data);
}
