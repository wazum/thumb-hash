<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\FileInterface;
use Wazum\ThumbHash\Configuration\ThumbHashConfiguration;
use Wazum\ThumbHash\Image\ImageProcessorFactory;
use Wazum\ThumbHash\Service\ThumbHashGenerator;

final class ThumbHashGeneratorTest extends TestCase
{
    private function createFactoryUsingGd(): ImageProcessorFactory
    {
        $extConf = $this->createMock(ExtensionConfiguration::class);
        $extConf->method('get')->willReturn([
            'imageProcessor' => 'gd',
        ]);
        $config = new ThumbHashConfiguration($extConf);

        return new ImageProcessorFactory($config);
    }

    #[Test]
    public function generatesHashFromValidFile(): void
    {
        $factory = $this->createFactoryUsingGd();
        $generator = new ThumbHashGenerator($factory);

        $file = $this->createMock(FileInterface::class);
        $file->method('getContents')
            ->willReturn(\file_get_contents(__DIR__ . '/../../Fixtures/lightning-strikes.jpg'));

        $hash = $generator->generateFromFile($file);

        $this->assertNotNull($hash);
        $this->assertIsString($hash);
        $this->assertSame('E/cNFYJWaHeMh3eAeHh3eWaAWFMJ', $hash);
    }

    #[Test]
    public function returnsNullForEmptyFileContent(): void
    {
        $factory = $this->createFactoryUsingGd();
        $generator = new ThumbHashGenerator($factory);

        $file = $this->createMock(FileInterface::class);
        $file->method('getContents')->willReturn('');

        $hash = $generator->generateFromFile($file);

        $this->assertNull($hash);
    }

    #[Test]
    public function returnsNullWhenFileThrowsException(): void
    {
        $factory = $this->createFactoryUsingGd();
        $generator = new ThumbHashGenerator($factory);

        $file = $this->createMock(FileInterface::class);
        $file->method('getContents')->willThrowException(new \Exception('File read error'));

        $hash = $generator->generateFromFile($file);

        $this->assertNull($hash);
    }

    #[Test]
    public function returnsNullForInvalidImageContent(): void
    {
        $factory = $this->createFactoryUsingGd();
        $generator = new ThumbHashGenerator($factory);

        $file = $this->createMock(FileInterface::class);
        $file->method('getContents')->willReturn('not an image');

        $hash = $generator->generateFromFile($file);

        $this->assertNull($hash);
    }
}
