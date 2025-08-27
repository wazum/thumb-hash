<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Unit\Image;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Wazum\ThumbHash\Image\GdImageProcessor;

final class GdImageProcessorTest extends TestCase
{
    private GdImageProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->processor = new GdImageProcessor();
    }

    #[Test]
    public function extractsPixelsFromJpegImage(): void
    {
        $imagePath = __DIR__ . '/../../Fixtures/lightning-strikes.jpg';
        $content = \file_get_contents($imagePath);
        if ($content === false) {
            $this->fail('Failed to read test image');
        }

        [$width, $height, $pixels] = $this->processor->extractPixels($content);

        $this->assertSame(100, $width);
        $this->assertSame(67, $height);
        $this->assertCount(100 * 67 * 4, $pixels); // RGBA for each pixel
        $this->assertContainsOnlyInt($pixels);
    }

    #[Test]
    public function correctlyHandlesAlphaChannel(): void
    {
        // Create a 2x2 image with transparency
        $image = \imagecreatetruecolor(2, 2);
        if ($image === false) {
            $this->fail('Failed to create test image');
        }
        \imagealphablending($image, false);
        \imagesavealpha($image, true);

        // Set different alpha values for each pixel
        // GD uses 0-127 for alpha (0 = opaque, 127 = transparent)
        // We cast all to int to satisfy type checker
        // Fully opaque red
        \imagesetpixel($image, 0, 0, (int) \imagecolorallocatealpha($image, 255, 0, 0, 0));
        // Fully transparent green
        \imagesetpixel($image, 1, 0, (int) \imagecolorallocatealpha($image, 0, 255, 0, 127));
        // Half transparent blue
        \imagesetpixel($image, 0, 1, (int) \imagecolorallocatealpha($image, 0, 0, 255, 64));
        // Slightly transparent yellow
        \imagesetpixel($image, 1, 1, (int) \imagecolorallocatealpha($image, 255, 255, 0, 32));

        \ob_start();
        \imagepng($image);
        $content = \ob_get_clean();
        if ($content === false) {
            $this->fail('Failed to generate PNG');
        }
        \imagedestroy($image);

        [$width, $height, $pixels] = $this->processor->extractPixels($content);

        $this->assertSame(2, $width);
        $this->assertSame(2, $height);

        // Check alpha values (converted from GD's 0-127 to standard 0-255)
        $this->assertSame(255, $pixels[3], 'First pixel should be fully opaque');
        $this->assertSame(0, $pixels[7], 'Second pixel should be fully transparent');
        $this->assertSame(126, $pixels[11], 'Third pixel should be half transparent');
        $this->assertSame(190, $pixels[15], 'Fourth pixel should be slightly transparent');
    }

    #[Test]
    public function resizesLargeImageToMaximum100Pixels(): void
    {
        // Create a 200x150 test image
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

    #[Test]
    public function throwsExceptionForInvalidImageData(): void
    {
        $invalidContent = 'not an image';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create image from content');

        $this->processor->extractPixels($invalidContent);
    }

    #[Test]
    public function preservesAlphaChannelDuringResize(): void
    {
        // Create a 200x200 PNG with transparency that will trigger resize
        $image = \imagecreatetruecolor(200, 200);
        if ($image === false) {
            $this->fail('Failed to create test image');
        }

        // Enable alpha channel
        \imagealphablending($image, false);
        \imagesavealpha($image, true);

        // Fill with transparent background
        $transparent = \imagecolorallocatealpha($image, 0, 0, 0, 127);
        if ($transparent === false) {
            $this->fail('Failed to allocate transparent color');
        }
        \imagefill($image, 0, 0, $transparent);

        // Add a semi-transparent red square in the center
        $semiTransparentRed = \imagecolorallocatealpha($image, 255, 0, 0, 63); // 50% transparent
        if ($semiTransparentRed === false) {
            $this->fail('Failed to allocate semi-transparent color');
        }
        \imagefilledrectangle($image, 75, 75, 125, 125, $semiTransparentRed);

        // Convert to PNG content
        \ob_start();
        \imagepng($image);
        $content = \ob_get_clean();
        if ($content === false) {
            $this->fail('Failed to generate PNG');
        }
        \imagedestroy($image);

        // Process through GdImageProcessor (will resize to 100x100)
        [$width, $height, $pixels] = $this->processor->extractPixels($content);

        $this->assertSame(100, $width);
        $this->assertSame(100, $height);

        // Check corner pixels are transparent (alpha = 0)
        // Top-left corner
        $this->assertSame(0, $pixels[3], 'Top-left corner should be fully transparent');

        // Top-right corner (pixel at x=99, y=0)
        $topRightIndex = (99 * 4) + 3;
        $this->assertSame(0, $pixels[$topRightIndex], 'Top-right corner should be fully transparent');

        // Check center has semi-transparent red (approximately)
        // Center pixel at x=50, y=50
        $centerIndex = (50 * 100 + 50) * 4;
        $this->assertSame(255, $pixels[$centerIndex], 'Center should be red');
        $this->assertSame(0, $pixels[$centerIndex + 1], 'Center green should be 0');
        $this->assertSame(0, $pixels[$centerIndex + 2], 'Center blue should be 0');
        // Alpha should be around 128 (50% opaque)
        $this->assertGreaterThan(100, $pixels[$centerIndex + 3], 'Center should be semi-transparent');
        $this->assertLessThan(150, $pixels[$centerIndex + 3], 'Center should be semi-transparent');
    }
}
