<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Unit\Image;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Wazum\ThumbHash\Image\ImagickImageProcessor;

final class ImagickImageProcessorTest extends TestCase
{
    private ?ImagickImageProcessor $processor = null;

    #[\Override]
    protected function setUp(): void
    {
        if (!\extension_loaded('imagick')) {
            $this->markTestSkipped('Imagick extension not available');
        }
        $this->processor = new ImagickImageProcessor();
    }

    #[Test]
    public function extractsPixelsFromJpegImage(): void
    {
        if ($this->processor === null) {
            $this->fail('Processor not initialized');
        }

        $imagePath = __DIR__ . '/../../Fixtures/lightning-strikes.jpg';
        $content = \file_get_contents($imagePath);
        if ($content === false) {
            $this->fail('Failed to read test image');
        }

        [$width, $height, $pixels] = $this->processor->extractPixels($content);

        $this->assertSame(100, $width);
        $this->assertSame(67, $height);
        // RGBA for each pixel
        $this->assertCount(100 * 67 * 4, $pixels);
        $this->assertContainsOnlyInt($pixels);
    }

    #[Test]
    public function correctlyHandlesAlphaChannel(): void
    {
        if ($this->processor === null) {
            $this->fail('Processor not initialized');
        }

        // Create a 2x2 image with transparency using Imagick
        $image = new \Imagick();
        $image->newImage(2, 2, 'none');
        $image->setImageFormat('png');

        // Create pixel iterator to set individual pixels
        $iterator = $image->getPixelIterator();
        $pixels = [
            [255, 0, 0, 255],    // Fully opaque red
            [0, 255, 0, 0],      // Fully transparent green
            [0, 0, 255, 126],    // Half transparent blue
            [255, 255, 0, 190],  // Slightly transparent yellow
        ];

        $pixelIndex = 0;
        foreach ($iterator as $pixelRow) {
            /** @var \ImagickPixel $pixel */
            foreach ($pixelRow ?? [] as $pixel) {
                if ($pixelIndex >= 4) {
                    break;
                }
                $p = $pixels[$pixelIndex++];
                // Imagick uses normalized values (0-1) for setColor
                // Our test uses 0=transparent, 255=opaque, same as Imagick expects
                $pixel->setColor(\sprintf('rgba(%d,%d,%d,%f)', $p[0], $p[1], $p[2], $p[3] / 255));
                $iterator->syncIterator();
            }
        }

        $content = $image->getImageBlob();
        $image->destroy();

        [$width, $height, $extractedPixels] = $this->processor->extractPixels($content);

        $this->assertSame(2, $width);
        $this->assertSame(2, $height);

        // Check alpha values
        $this->assertSame(255, $extractedPixels[3], 'First pixel should be fully opaque');
        $this->assertSame(0, $extractedPixels[7], 'Second pixel should be fully transparent');
        $this->assertSame(126, $extractedPixels[11], 'Third pixel should be half transparent');
        $this->assertSame(190, $extractedPixels[15], 'Fourth pixel should be slightly transparent');
    }

    #[Test]
    public function resizesLargeImageToMaximum100Pixels(): void
    {
        if ($this->processor === null) {
            $this->fail('Processor not initialized');
        }

        // Create a 200x150 test image using GD (simpler than Imagick for test data)
        $image = \imagecreatetruecolor(200, 150);
        if ($image === false) {
            $this->fail('Failed to create test image');
        }
        $color = \imagecolorallocate($image, 255, 0, 0);
        if ($color === false) {
            $this->fail('Failed to allocate color');
        }
        \imagefill($image, 0, 0, $color);
        \ob_start();
        \imagejpeg($image);
        $content = \ob_get_clean();
        if ($content === false) {
            $this->fail('Failed to generate JPEG');
        }
        \imagedestroy($image);

        [$width, $height, $pixels] = $this->processor->extractPixels($content);

        // Should be scaled down to 100x75 (maintaining aspect ratio)
        $this->assertSame(100, $width);
        $this->assertSame(75, $height);
        $this->assertCount(100 * 75 * 4, $pixels);
    }
}
