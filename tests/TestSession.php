<?php

namespace fkooman\RemoteStorage\Tests;

use fkooman\RemoteStorage\Http\SessionInterface;

class TestSession implements SessionInterface
{
    /** @var array */
    private $sessionData = [];

    /**
     * Get the session ID.
     */
    public function id(): string
    {
        return '12345';
    }

    public function set(string $k, string $v): void
    {
        $this->sessionData[$k] = $v;
    }

    public function delete(string $k): void
    {
        if ($this->has($k)) {
            unset($this->sessionData[$k]);
        }
    }

    public function has(string $k): bool
    {
        return \array_key_exists($k, $this->sessionData);
    }

    public function get(string $k): ?string
    {
        if (!$this->has($k)) {
            return null;
        }

        return $this->sessionData[$k];
    }

    public function destroy(): void
    {
        $this->sessionData = [];
    }
}
