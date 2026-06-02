<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use Wazum\ThumbHash\EventListener\FileProcessingEventListener;

final class FileEventListenerTest extends AbstractFunctionalTestCase
{
    #[Test]
    public function addingAFileStoresHashInFileMetadata(): void
    {
        $storage = $this->defaultStorage();

        $source = $this->instancePath . '/typo3temp/source.jpg';
        @\mkdir(\dirname($source), 0o777, true);
        \copy($this->fixturePath(), $source);

        $file = $storage->addFile($source, $storage->getRootLevelFolder(), 'uploaded.jpg');

        $hash = $this->readHash('sys_file_metadata', 'file', (int) $file->getUid());
        self::assertNotNull($hash, 'AfterFileAddedEvent should have stored a hash');
        self::assertNotSame('', $hash);
    }

    #[Test]
    public function processingAFileStoresHashInProcessedFile(): void
    {
        $file = $this->provideIndexedFile();

        $processedFile = $file->process(
            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
            ['width' => 16, 'height' => 16],
        );

        self::assertFalse(
            $processedFile->usesOriginalFile(),
            'A distinct processed file must be created for the listener to act on',
        );

        $hash = $this->readHash('sys_file_processedfile', 'uid', (int) $processedFile->getUid());
        self::assertNotNull($hash, 'AfterFileProcessingEvent should have stored a hash');
        self::assertNotSame('', $hash);
    }

    #[Test]
    public function cachedProcessedFileWithExistingHashIsNotRegenerated(): void
    {
        $file = $this->provideIndexedFile();
        $processedFile = $file->process(
            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
            ['width' => 16, 'height' => 16],
        );
        $uid = (int) $processedFile->getUid();

        // Replace the real hash with a sentinel; if the listener regenerates on a
        // cache hit, the sentinel is overwritten with the real hash.
        $this->getConnectionPool()
            ->getConnectionForTable('sys_file_processedfile')
            ->update('sys_file_processedfile', ['thumb_hash' => 'SENTINEL'], ['uid' => $uid]);

        // Re-fetch the variant as it would arrive on a later request: reconstituted
        // from the database, so isUpdated() is false (a cache hit, not reprocessed).
        $cached = $this->get(ProcessedFileRepository::class)->findByUid($uid);
        self::assertFalse($cached->isUpdated());

        $event = new AfterFileProcessingEvent(
            $this->createMock(DriverInterface::class),
            $cached,
            $file,
            'Image.CropScaleMask',
            ['width' => 16, 'height' => 16],
        );
        $this->get(FileProcessingEventListener::class)->handleFileProcessing($event);

        self::assertSame(
            'SENTINEL',
            $this->readHash('sys_file_processedfile', 'uid', $uid),
            'A cached, already-hashed variant must not be regenerated',
        );
    }
}
