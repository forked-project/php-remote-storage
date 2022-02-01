<?php

declare(strict_types=1);

/*
 * php-remote-storage - PHP remoteStorage implementation
 *
 * Copyright: 2016 SURFnet
 * Copyright: 2022 François Kooman <fkooman@tuxed.net>
 *
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace fkooman\RemoteStorage;

use fkooman\RemoteStorage\Exception\PathException;

class Path
{
    private string $p;

    /** @var array<string> */
    private array $pathParts = [];

    public function __construct(string $p)
    {
        // MUST contain at least one slash and start with it
        if (0 !== strpos($p, '/')) {
            throw new PathException('invalid path: does not start with /');
        }

        // MUST NOT contain encoded "/"
        if (false !== stripos($p, '%2f')) {
            throw new PathException('invalid path: contains encoded "/"');
        }

        // MUST NOT contain encoded "\0"
        if (false !== strpos($p, '%00')) {
            throw new PathException('invalid path: contains encoded "\0"');
        }

        // MUST NOT contain ".."
        if (false !== strpos($p, '..')) {
            throw new PathException('invalid path: contains ..');
        }

        // MUST NOT contain "%2e%2e"
        if (false !== stripos($p, '%2e%2e')) {
            throw new PathException('invalid path: contains encoded ".."');
        }

        // MUST NOT contain "//"
        if (false !== strpos($p, '//')) {
            throw new PathException('invalid path: contains //');
        }

        // MUST contain a user
        $pathParts = explode('/', $p);
        if (\count($pathParts) < 3) {
            throw new PathException('invalid path: no user specified');
        }

        foreach ($pathParts as $pathPart) {
            $this->pathParts[] = rawurldecode($pathPart);
        }
        $this->p = implode('/', $this->pathParts);
    }

    public function getPath(): string
    {
        return $this->p;
    }

    public function getIsPublic(): bool
    {
        return \count($this->pathParts) > 3 && 'public' === $this->pathParts[2];
    }

    public function getUserId(): string
    {
        return $this->pathParts[1];
    }

    public function getIsFolder(): bool
    {
        return empty($this->pathParts[\count($this->pathParts) - 1]);
    }

    public function getIsDocument(): bool
    {
        return !$this->getIsFolder();
    }

    public function getModuleName(): ?string
    {
        $moduleNamePosition = $this->getIsPublic() ? 3 : 2;
        if (\count($this->pathParts) > $moduleNamePosition + 1) {
            return $this->pathParts[$moduleNamePosition];
        }

        return null;
    }

    public function getFolderPath(): string
    {
        if ($this->getIsFolder()) {
            return $this->p;
        }

        return substr($this->p, 0, strrpos($this->p, '/') + 1);
    }

    /**
     * @return array<string>
     */
    public function getFolderTreeToUserRoot(): array
    {
        $folderTree = [];
        $p = $this->getFolderPath();
        do {
            $folderTree[] = $p;

            // remove from last "/" to previous "/", e.g.:
            // "/foo/bar/baz/" -> "/foo/bar/"

            // remove the last "/"
            $p = substr($p, 0, \strlen($p) - 1);
            // remove everything after the now last "/"
            $p = substr($p, 0, strrpos($p, '/') + 1);
        } while (substr_count($p, '/') > 1);

        return $folderTree;
    }

    /**
     * @return array<string>
     */
    public function getFolderTreeFromUserRoot(): array
    {
        return array_reverse($this->getFolderTreeToUserRoot());
    }
}
