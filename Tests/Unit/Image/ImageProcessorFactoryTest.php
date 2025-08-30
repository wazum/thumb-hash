<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Unit\Image;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use Wazum\ThumbHash\Configuration\ThumbHashConfiguration;
use Wazum\ThumbHash\Image\ImageProcessor;
use Wazum\ThumbHash\Image\ImageProcessorFactory;

final class ImageProcessorFactoryTest extends TestCase
{
    #[Test]
    public function createsImageProcessor(): void
    {
        // Arrange
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn([
            'imageProcessor' => 'auto',
        ]);
        $configuration = new ThumbHashConfiguration($extensionConfiguration);
        $factory = new ImageProcessorFactory($configuration);

        // Act
        $processor = $factory->create();

        // Assert
        $this->assertInstanceOf(ImageProcessor::class, $processor);
    }
}
