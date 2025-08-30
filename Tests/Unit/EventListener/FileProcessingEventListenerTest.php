<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileContentsSetEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use Wazum\ThumbHash\Configuration\ThumbHashConfiguration;
use Wazum\ThumbHash\EventListener\FileProcessingEventListener;
use Wazum\ThumbHash\Image\ImageProcessorFactory;
use Wazum\ThumbHash\Service\FileMetadataService;
use Wazum\ThumbHash\Service\ProcessedFileMetadataService;
use Wazum\ThumbHash\Service\ThumbHashGenerator;

final class FileProcessingEventListenerTest extends TestCase
{
    private FileProcessingEventListener $listener;
    private Connection $connection;

    #[\Override]
    protected function setUp(): void
    {
        // Mock only TYPO3 core dependencies
        $this->connection = $this->createMock(Connection::class);
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getConnectionForTable')->willReturn($this->connection);

        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn([
            'autoGenerate' => true,
            'allowedMimeTypes' => 'image/jpeg,image/png,image/gif',
            'excludedFolders' => '/_temp_/',
            'imageProcessor' => 'gd',
        ]);

        // Use real instances of our own classes
        $configuration = new ThumbHashConfiguration($extensionConfiguration);
        $fileMetadataService = new FileMetadataService($connectionPool);
        $processedFileMetadataService = new ProcessedFileMetadataService($connectionPool);
        $imageProcessorFactory = new ImageProcessorFactory($configuration);
        $generator = new ThumbHashGenerator($imageProcessorFactory);

        $this->listener = new FileProcessingEventListener(
            $configuration,
            $generator,
            $fileMetadataService,
            $processedFileMetadataService
        );
    }

    #[Test]
    public function processesFileOnAddedEvent(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getIdentifier')->willReturn('/fileadmin/test.jpg');
        $file->method('getContents')->willReturn(\file_get_contents(__DIR__ . '/../../Fixtures/lightning-strikes.jpg'));
        $file->method('getUid')->willReturn(123);
        $file->method('getSize')->willReturn(1024);

        $folder = $this->createMock(Folder::class);

        $this->connection->expects($this->once())
            ->method('update')
            ->with(
                'sys_file_metadata',
                ['thumb_hash' => 'E/cNFYJWaHeMh3eAeHh3eWaAWFMJ'],
                ['file' => 123]
            );

        $event = new AfterFileAddedEvent($file, $folder);
        $this->listener->handleFileAdded($event);
    }

    #[Test]
    public function processesFileOnReplacedEvent(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getIdentifier')->willReturn('/fileadmin/test.jpg');
        $file->method('getContents')->willReturn(\file_get_contents(__DIR__ . '/../../Fixtures/lightning-strikes.jpg'));
        $file->method('getUid')->willReturn(124);
        $file->method('getSize')->willReturn(1024);

        $this->connection->expects($this->once())
            ->method('update')
            ->with(
                'sys_file_metadata',
                ['thumb_hash' => 'E/cNFYJWaHeMh3eAeHh3eWaAWFMJ'],
                ['file' => 124]
            );

        $event = new AfterFileReplacedEvent($file, '/tmp/uploaded.jpg');
        $this->listener->handleFileReplaced($event);
    }

    #[Test]
    public function processesFileOnContentsSetEvent(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getIdentifier')->willReturn('/fileadmin/test.jpg');
        $file->method('getContents')->willReturn(\file_get_contents(__DIR__ . '/../../Fixtures/lightning-strikes.jpg'));
        $file->method('getUid')->willReturn(125);
        $file->method('getSize')->willReturn(1024);

        $this->connection->expects($this->once())
            ->method('update')
            ->with(
                'sys_file_metadata',
                ['thumb_hash' => 'E/cNFYJWaHeMh3eAeHh3eWaAWFMJ'],
                ['file' => 125]
            );

        $event = new AfterFileContentsSetEvent($file, 'dummy content');
        $this->listener->handleFileContentsSet($event);
    }

    #[Test]
    public function processesProcessedFileOnProcessingEvent(): void
    {
        $processedFile = $this->createMock(ProcessedFile::class);
        $processedFile->method('isImage')->willReturn(true);
        $processedFile->method('getContents')->willReturn(
            \file_get_contents(__DIR__ . '/../../Fixtures/lightning-strikes.jpg')
        );
        $processedFile->method('getUid')->willReturn(456);
        $processedFile->method('getSize')->willReturn(1024);

        $originalFile = $this->createMock(FileInterface::class);
        $driver = $this->createMock(DriverInterface::class);

        $this->connection->expects($this->once())
            ->method('update')
            ->with(
                'sys_file_processedfile',
                ['thumb_hash' => 'E/cNFYJWaHeMh3eAeHh3eWaAWFMJ'],
                ['uid' => 456]
            );

        $event = new AfterFileProcessingEvent(
            $driver,
            $processedFile,
            $originalFile,
            'Image.CropScaleMask',
            ['width' => 100, 'height' => 100]
        );

        $this->listener->handleFileProcessing($event);
    }

    #[Test]
    public function skipsNonImageProcessedFiles(): void
    {
        $processedFile = $this->createMock(ProcessedFile::class);
        $processedFile->method('isImage')->willReturn(false);

        $originalFile = $this->createMock(FileInterface::class);
        $driver = $this->createMock(DriverInterface::class);

        $this->connection->expects($this->never())
            ->method('update');

        $event = new AfterFileProcessingEvent(
            $driver,
            $processedFile,
            $originalFile,
            'Document.Process',
            []
        );

        $this->listener->handleFileProcessing($event);
    }

    #[Test]
    public function skipsProcessedFileThatUsesOriginal(): void
    {
        // This test represents the scenario where TYPO3 creates a ProcessedFile
        // that uses the original file (e.g., when original already meets size requirements)
        $processedFile = $this->createMock(ProcessedFile::class);
        $processedFile->method('usesOriginalFile')->willReturn(true);

        // isImage() should never be called when usesOriginalFile returns true
        $processedFile->expects($this->never())
            ->method('isImage');

        $originalFile = $this->createMock(FileInterface::class);
        $driver = $this->createMock(DriverInterface::class);

        // Should skip processing and not try to update database
        $this->connection->expects($this->never())
            ->method('update');

        $event = new AfterFileProcessingEvent(
            $driver,
            $processedFile,
            $originalFile,
            'Image.CropScaleMask',
            ['width' => '32c', 'height' => '32c']
        );

        $this->listener->handleFileProcessing($event);
    }

    #[Test]
    public function skipsFileWhenAutoGenerationDisabled(): void
    {
        // Create a new listener with auto-generation disabled
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn([
            'autoGenerate' => false,
            'allowedMimeTypes' => 'image/jpeg,image/png,image/gif',
            'excludedFolders' => '/_temp_/',
            'imageProcessor' => 'gd',
        ]);

        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getConnectionForTable')->willReturn($this->connection);

        $configuration = new ThumbHashConfiguration($extensionConfiguration);
        $fileMetadataService = new FileMetadataService($connectionPool);
        $processedFileMetadataService = new ProcessedFileMetadataService($connectionPool);
        $imageProcessorFactory = new ImageProcessorFactory($configuration);
        $generator = new ThumbHashGenerator($imageProcessorFactory);

        $listener = new FileProcessingEventListener(
            $configuration,
            $generator,
            $fileMetadataService,
            $processedFileMetadataService
        );

        $file = $this->createMock(File::class);
        $folder = $this->createMock(Folder::class);

        $this->connection->expects($this->never())
            ->method('update');

        $event = new AfterFileAddedEvent($file, $folder);
        $listener->handleFileAdded($event);
    }

    #[Test]
    public function skipsFileWithDisallowedMimeType(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getIdentifier')->willReturn('/fileadmin/test.pdf');

        $folder = $this->createMock(Folder::class);

        $this->connection->expects($this->never())
            ->method('update');

        $event = new AfterFileAddedEvent($file, $folder);
        $this->listener->handleFileAdded($event);
    }

    #[Test]
    public function skipsFileInExcludedFolder(): void
    {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getIdentifier')->willReturn('/fileadmin/_temp_/test.jpg');

        $folder = $this->createMock(Folder::class);

        $this->connection->expects($this->never())
            ->method('update');

        $event = new AfterFileAddedEvent($file, $folder);
        $this->listener->handleFileAdded($event);
    }

    #[Test]
    public function skipsProcessedFileInsteadOfFile(): void
    {
        $processedFile = $this->createMock(ProcessedFile::class);
        $folder = $this->createMock(Folder::class);

        $this->connection->expects($this->never())
            ->method('update');

        $event = new AfterFileAddedEvent($processedFile, $folder);
        $this->listener->handleFileAdded($event);
    }
}
