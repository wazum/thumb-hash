<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Unit\Image;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Wazum\ThumbHash\Image\ImageProcessor;
use Wazum\ThumbHash\Image\ImageProcessorFactory;

final class ImageProcessorFactoryTest extends TestCase
{
    #[Test]
    public function createsImageProcessor(): void
    {
        // Arrange
        $factory = new ImageProcessorFactory();

        // Act
        $processor = $factory->create();

        // Assert - should return an ImageProcessor implementation
        $this->assertInstanceOf(ImageProcessor::class, $processor);
    }
}
