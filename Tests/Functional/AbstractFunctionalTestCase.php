<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Functional;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractFunctionalTestCase extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wazum/thumb-hash',
    ];

    /**
     * Absolute path to the JPEG fixture shipped with the extension (300x200).
     */
    protected function fixturePath(): string
    {
        return $this->instancePath . '/typo3conf/ext/thumb_hash/Tests/Fixtures/lightning-strikes.jpg';
    }

    /**
     * Auto-creates the default fileadmin storage (UID 1) and returns it.
     */
    protected function defaultStorage(): ResourceStorage
    {
        $storageRepository = $this->get(StorageRepository::class);
        // Triggers auto-creation of the default "fileadmin/" storage (UID 1).
        $storageRepository->findAll();

        $storage = $storageRepository->findByUid(1);
        self::assertInstanceOf(ResourceStorage::class, $storage);

        return $storage;
    }

    /**
     * Copies the fixture into fileadmin and indexes it (without firing the
     * AfterFileAddedEvent), returning the indexed File with an empty thumb_hash.
     */
    protected function provideIndexedFile(string $name = 'lightning.jpg'): File
    {
        $storage = $this->defaultStorage();

        $fileadminPath = $this->instancePath . '/fileadmin/';
        if (!\is_dir($fileadminPath)) {
            \mkdir($fileadminPath, 0o777, true);
        }
        \copy($this->fixturePath(), $fileadminPath . $name);

        $file = $storage->getFile('/' . $name);
        self::assertInstanceOf(File::class, $file);

        return $file;
    }

    protected function readHash(string $table, string $identifierColumn, int $identifier): ?string
    {
        $value = $this->getConnectionPool()
            ->getConnectionForTable($table)
            ->select(['thumb_hash'], $table, [$identifierColumn => $identifier])
            ->fetchOne();

        return false === $value || '' === $value ? null : (string) $value;
    }
}
