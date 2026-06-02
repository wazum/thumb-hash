<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\ProcessedFile;

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
}
