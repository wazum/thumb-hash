<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\ViewHelpers;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Wazum\ThumbHash\Service\FileMetadataService;
use Wazum\ThumbHash\Service\ProcessedFileMetadataService;
use Wazum\ThumbHash\Service\ThumbHashGenerator;

/**
 * @psalm-suppress MissingOverrideAttribute
 *
 * ViewHelper to generate thumbhash for images
 *
 * Usage:
 * {namespace thumbhash=Wazum\ThumbHash\ViewHelpers}
 * <f:image image="{file}" additionalAttributes="{data-thumbhash: '{thumbhash:thumbHash(file: file)}'}" />
 */
final class ThumbHashViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly ThumbHashGenerator $generator,
        private readonly FileMetadataService $fileMetadataService,
        private readonly ProcessedFileMetadataService $processedFileMetadataService,
    ) {
    }

    #[\Override]
    public function initializeArguments(): void
    {
        $this->registerArgument('file', 'object', 'File, FileReference or ProcessedFile object', true);
    }

    /**
     * @psalm-suppress MissingReturnType
     * @psalm-suppress MethodSignatureMismatch
     *
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function render()
    {
        $file = $this->arguments['file'];

        return match (true) {
            $file instanceof File => $this->processRegularFile($file),
            $file instanceof FileReference => $this->processRegularFile($file->getOriginalFile()),
            $file instanceof ProcessedFile => $this->processProcessedFile($file),
            default => '',
        };
    }

    private function processRegularFile(File $file): string
    {
        // Try metadata first, generate if missing
        return $this->fileMetadataService->getHash($file)
            ?? $this->generateAndStore($file);
    }

    private function processProcessedFile(ProcessedFile $file): string
    {
        // Try to get existing hash first, generate if missing
        return $this->processedFileMetadataService->getHash($file)
            ?? $this->generateAndStoreProcessedFile($file);
    }

    private function generateAndStore(File $file): string
    {
        $hash = $this->generator->generateFromFile($file);
        if ($hash !== null) {
            $this->fileMetadataService->storeHash($file, $hash);
        }

        return $hash ?? '';
    }

    private function generateAndStoreProcessedFile(ProcessedFile $file): string
    {
        $hash = $this->generator->generateFromFile($file);
        if ($hash !== null) {
            $this->processedFileMetadataService->storeHash($file, $hash);
        }

        return $hash ?? '';
    }
}
