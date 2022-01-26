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

use fkooman\RemoteStorage\Exception\MetadataStorageException;
use PDO;

class MetadataStorage
{
    /** @var PDO */
    private $db;

    /** @var RandomInterface */
    private $random;

    public function __construct(PDO $db, RandomInterface $random = null)
    {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // $db->query('PRAGMA foreign_keys = ON');
        $this->db = $db;
        if (null === $random) {
            $random = new Random();
        }
        $this->random = $random;
    }

    public function getVersion(Path $p)
    {
        $md = $this->getMetadata($p);

        return null !== $md ? $md['version'] : null;
    }

    public function getContentType(Path $p)
    {
        $md = $this->getMetadata($p);

        return null !== $md ? $md['content_type'] : null;
    }

    public function updateFolder(Path $p)
    {
        if (!$p->getIsFolder()) {
            throw new MetadataStorageException('not a folder');
        }

        return $this->updateDocument($p, null);
    }

    /**
     * We have a very weird version update method by including a sequence number
     * that makes it easy for tests to see if there is correct behavior, a sequence
     * number is not enough though as deleting a file would reset the sequence number and
     * thus make it possible to have files with different content to have the same
     * sequence number in the same location, but in order to check if all versions
     * are updated up to the root we have to do this this way...
     *
     * @param mixed $contentType
     */
    public function updateDocument(Path $p, $contentType): void
    {
        $currentVersion = $this->getVersion($p);
        if (null === $currentVersion) {
            $newVersion = '1:'.$this->random->get(8);
            $stmt = $this->db->prepare(
                'INSERT INTO md (path, content_type, version) VALUES(:path, :content_type, :version)'
            );
        } else {
            $explodedData = explode(':', $currentVersion);
            $newVersion = sprintf('%d:%s', $explodedData[0] + 1, $this->random->get(8));
            $stmt = $this->db->prepare(
                'UPDATE md SET version = :version, content_type = :content_type WHERE path = :path'
            );
        }

        $stmt->bindValue(':path', $p->getPath(), PDO::PARAM_STR);
        $stmt->bindValue(':content_type', $contentType, PDO::PARAM_STR);
        $stmt->bindValue(':version', $newVersion, PDO::PARAM_STR);
        $stmt->execute();

        if (1 !== $stmt->rowCount()) {
            throw new MetadataStorageException('unable to update node');
        }
    }

    public function deleteNode(Path $p): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM md WHERE path = :path'
        );
        $stmt->bindValue(':path', $p->getPath(), PDO::PARAM_STR);
        $stmt->execute();

        if (1 !== $stmt->rowCount()) {
            throw new MetadataStorageException('unable to delete node');
        }
    }

    public static function createTableQueries()
    {
        return [
            'CREATE TABLE IF NOT EXISTS md (
                path VARCHAR(255) NOT NULL,
                content_type VARCHAR(255) DEFAULT NULL,
                version VARCHAR(255) NOT NULL,
                UNIQUE (path)
            )',
        ];
    }

    public function init(): void
    {
        $queries = self::createTableQueries();
        foreach ($queries as $q) {
            $this->db->query($q);
        }
    }

    /**
     * Get the version of the path which can be either a folder or document.
     *
     * @param $path The full path to the folder or document
     * @returns the version of the path, or null if path does not exist
     */
    private function getMetadata(Path $p)
    {
        $stmt = $this->db->prepare(
            'SELECT version, content_type FROM md WHERE path = :path'
        );
        $stmt->bindValue(':path', $p->getPath(), PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (false !== $result) {
            return $result;
        }
    }
}
