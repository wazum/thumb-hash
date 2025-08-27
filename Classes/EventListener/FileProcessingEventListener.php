<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\EventListener;

use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileContentsSetEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use Wazum\ThumbHash\Configuration\ThumbHashConfiguration;
use Wazum\ThumbHash\Service\FileMetadataService;
use Wazum\ThumbHash\Service\ProcessedFileMetadataService;
use Wazum\ThumbHash\Service\ThumbHashGenerator;
use Wazum\ThumbHash\Utility\PathMatcher;

final readonly class FileProcessingEventListener
{
    public function __construct(
        private ThumbHashConfiguration $configuration,
        private ThumbHashGenerator $generator,
        private FileMetadataService $fileMetadataService,
        private ProcessedFileMetadataService $processedFileMetadataService,
    ) {
    }

    public function handleFileAdded(AfterFileAddedEvent $event): void
    {
        $this->processFile($event->getFile());
    }

    public function handleFileReplaced(AfterFileReplacedEvent $event): void
    {
        $this->processFile($event->getFile());
    }

    public function handleFileContentsSet(AfterFileContentsSetEvent $event): void
    {
        $this->processFile($event->getFile());
    }

    public function handleFileProcessing(AfterFileProcessingEvent $event): void
    {
        $processedFile = $event->getProcessedFile();

        // Skip if processed file uses the original file
        // (original already meets requirements, no separate file created)
        if ($processedFile->usesOriginalFile()) {
            return;
        }

        try {
            if (!$processedFile->isImage()) {
                return;
            }
        } catch (\Exception) {
            return;
        }

        // Generate and store hash for processed file
        $hash = $this->generator->generateFromFile($processedFile);
        if ($hash !== null) {
            $this->processedFileMetadataService->storeHash($processedFile, $hash);
        }
    }

    private function processFile(FileInterface $file): void
    {
        if (!$file instanceof File || !$this->shouldProcess($file)) {
            return;
        }

        $hash = $this->generator->generateFromFile($file);
        $hash && $this->fileMetadataService->storeHash($file, $hash);
    }

    private function shouldProcess(File $file): bool
    {
        return $this->isAutoGenerationEnabled()
            && $this->isFileTypeAllowed($file)
            && !$this->isFileInExcludedFolder($file);
    }

    private function isAutoGenerationEnabled(): bool
    {
        return $this->configuration->isAutoGenerateEnabled();
    }

    private function isFileTypeAllowed(File $file): bool
    {
        $mimeType = $file->getMimeType();
        $allowedMimeTypes = $this->configuration->getAllowedMimeTypes();

        return \in_array($mimeType, $allowedMimeTypes, true);
    }

    private function isFileInExcludedFolder(File $file): bool
    {
        return PathMatcher::matches(
            $file->getIdentifier(),
            $this->configuration->getExcludedFolders()
        );
    }
}
