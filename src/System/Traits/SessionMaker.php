<?php

namespace Igniter\System\Traits;

trait SessionMaker
{
    /**
     * Retrieves key/value pair from session data.
     */
    public function getSession(?string $key = null, mixed $default = null): mixed
    {
        $sessionKey = $this->makeSessionKey();
        if (!is_null($key)) {
            $sessionKey .= '.'.$key;
        }

        return session()->get($sessionKey, $default);
    }

    /**
     * Saves key/value pair in to session data.
     */
    public function putSession(string $key, mixed $value): void
    {
        session()->put($this->makeSessionKey().'.'.$key, $value);
    }

    public function hasSession(string $key): bool
    {
        return session()->has($this->makeSessionKey().'.'.$key);
    }

    /**
     * Saves key/value pair in to session temporary data.
     */
    public function flashSession(string $key, mixed $value): void
    {
        session()->flash($this->makeSessionKey().'.'.$key, $value);
    }

    public function forgetSession(string $key): void
    {
        session()->forget($this->makeSessionKey().'.'.$key);
    }

    public function resetSession(): void
    {
        session()->forget($this->makeSessionKey());
    }

    public function setSessionKey(string $key): self
    {
        $this->sessionKey = $key;

        return $this;
    }

    /**
     * Returns a unique session identifier for this location.
     */
    protected function makeSessionKey(): string
    {
        if (isset($this->sessionKey)) {
            return $this->sessionKey;
        }

        return get_class_id(get_class($this));
    }
}
