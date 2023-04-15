<?php

namespace Igniter\System\Traits;

trait SessionMaker
{
    /**
     * Retrieves key/value pair from session data.
     *
     * @param string $key Unique key for the data store.
     * @param string $default A default value to use when value is not found.
     */
    public function getSession(string|null $key = null, mixed $default = null): mixed
    {
        $sessionKey = $this->makeSessionKey();
        $sessionData = [];

        if (!is_null($cached = session()->get($sessionKey))) {
            $sessionData = $this->decodeSessionData($cached);
        }

        return is_null($key) ? $sessionData : ($sessionData[$key] ?? $default);
    }

    /**
     * Saves key/value pair in to session data.
     *
     * @param string $key Unique key for the data store.
     * @param mixed $value The value to store.
     */
    public function putSession(string $key, mixed $value): void
    {
        $sessionKey = $this->makeSessionKey();

        $sessionData = $this->getSession();
        $sessionData[$key] = $value;

        session()->put($sessionKey, $this->encodeSessionData($sessionData));
    }

    public function hasSession(string $key): bool
    {
        $sessionData = $this->getSession();

        return array_key_exists($key, $sessionData);
    }

    /**
     * Saves key/value pair in to session temporary data.
     *
     * @param string $key Unique key for the data store.
     * @param mixed $value The value to store.
     */
    public function flashSession(string $key, mixed $value): void
    {
        $sessionKey = $this->makeSessionKey();

        $sessionData = $this->getSession();
        $sessionData[$key] = $value;

        session()->flash($sessionKey, $this->encodeSessionData($sessionData));
    }

    public function forgetSession(string $key): void
    {
        $sessionData = $this->getSession();
        unset($sessionData[$key]);

        $sessionKey = $this->makeSessionKey();
        session()->put($sessionKey, $this->encodeSessionData($sessionData));
    }

    public function resetSession(): void
    {
        $sessionKey = $this->makeSessionKey();
        session()->forget($sessionKey);
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

    protected function encodeSessionData($data): string|null
    {
        if (is_null($data)) {
            return null;
        }

        if (!isset($this->encodeSession) || $this->encodeSession === true) {
            $data = base64_encode(serialize($data));
        }

        return $data;
    }

    protected function decodeSessionData(string $data): mixed
    {
        if (!is_string($data)) {
            return null;
        }

        $encodeSession = (!isset($this->encodeSession) || $this->encodeSession === true);

        if ($encodeSession || $data) {
            $data = @unserialize(@base64_decode($data));
        }

        return $data;
    }
}
