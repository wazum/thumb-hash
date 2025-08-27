<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\FileInterface;
use Wazum\ThumbHash\Image\ImageProcessorFactory;
use Wazum\ThumbHash\Service\ThumbHashGenerator;

final class ThumbHashGeneratorTest extends TestCase
{
    #[Test]
    public function generatesHashFromValidFile(): void
    {
        $factory = new ImageProcessorFactory();
        $generator = new ThumbHashGenerator($factory);

        $file = $this->createMock(FileInterface::class);
        $file->method('getContents')
            ->willReturn(\file_get_contents(__DIR__ . '/../../Fixtures/lightning-strikes.jpg'));

        $hash = $generator->generateFromFile($file);

        $this->assertNotNull($hash);
        $this->assertIsString($hash);
        // Known hash for lightning-strikes.jpg
        $this->assertSame('E/cNFYJWaHeMh3eAeHh3eWaAWFMJ', $hash);
    }

    #[Test]
    public function returnsNullForEmptyFileContent(): void
    {
        $factory = new ImageProcessorFactory();
        $generator = new ThumbHashGenerator($factory);

        $file = $this->createMock(FileInterface::class);
        $file->method('getContents')->willReturn('');

        $hash = $generator->generateFromFile($file);

        $this->assertNull($hash);
    }

    #[Test]
    public function returnsNullWhenFileThrowsException(): void
    {
        $factory = new ImageProcessorFactory();
        $generator = new ThumbHashGenerator($factory);

        $file = $this->createMock(FileInterface::class);
        $file->method('getContents')->willThrowException(new \Exception('File read error'));

        $hash = $generator->generateFromFile($file);

        $this->assertNull($hash);
    }

    #[Test]
    public function returnsNullForInvalidImageContent(): void
    {
        $factory = new ImageProcessorFactory();
        $generator = new ThumbHashGenerator($factory);

        $file = $this->createMock(FileInterface::class);
        $file->method('getContents')->willReturn('not an image');

        $hash = $generator->generateFromFile($file);

        $this->assertNull($hash);
    }
}
