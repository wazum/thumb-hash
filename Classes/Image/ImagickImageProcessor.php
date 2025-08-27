<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Image;

final class ImagickImageProcessor implements ImageProcessor
{
    private const RESOURCE_MEMORY_MB = 128;
    private const RESOURCE_MAP_MB = 256;
    private const RESOURCE_AREA_PIXELS = 50_000_000;

    /**
     * @throws \ImagickException
     * @throws \ImagickPixelIteratorException
     */
    #[\Override]
    public function extractPixels(string $content): array
    {
        $oldMemory = \Imagick::getResourceLimit(\Imagick::RESOURCETYPE_MEMORY);
        $oldMap = \Imagick::getResourceLimit(\Imagick::RESOURCETYPE_MAP);
        $oldArea = \Imagick::getResourceLimit(\Imagick::RESOURCETYPE_AREA);

        \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, self::RESOURCE_MEMORY_MB * 1024 * 1024);
        \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MAP, self::RESOURCE_MAP_MB * 1024 * 1024);
        \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_AREA, self::RESOURCE_AREA_PIXELS);

        try {
            $image = new \Imagick();

            // Hint to decoder to only read at smaller resolution
            // This significantly reduces memory usage for large images
            $image->setSize(200, 200);

            $image->readImageBlob($content);

            $width = $image->getImageWidth();
            $height = $image->getImageHeight();

            list($width, $height) = $this->resizeOnDemand($image, $width, $height);

            $pixels = [];
            $iterator = $image->getPixelIterator();
            foreach ($iterator as $pixelRow) {
                foreach ($pixelRow as $pixel) {
                    // Get normalized color values (0..1 floats) for consistency across builds
                    $colors = $pixel->getColor(1);
                    // Convert to 0..255 integer range
                    $pixels[] = (int) \round($colors['r'] * 255);
                    $pixels[] = (int) \round($colors['g'] * 255);
                    $pixels[] = (int) \round($colors['b'] * 255);
                    // Imagick alpha: 0=transparent, 1=opaque (same as ThumbHash expects)
                    // Convert directly to 0..255 range (0=transparent, 255=opaque)
                    $pixels[] = (int) \round($colors['a'] * 255);
                }
                $iterator->syncIterator();
            }

            $image->clear();

            return [$width, $height, $pixels];
        } finally {
            \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, $oldMemory);
            \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_MAP, $oldMap);
            \Imagick::setResourceLimit(\Imagick::RESOURCETYPE_AREA, $oldArea);
        }
    }

    /**
     * return array{int, int}
     *
     * @throws \ImagickException
     */
    private function resizeOnDemand(\Imagick $image, int $width, int $height): array
    {
        if ($width <= 100 && $height <= 100) {
            return [$width, $height];
        }

        $scale = \min(100.0 / (float) $width, 100.0 / (float) $height);
        $newWidth = (int) \round((float) $width * $scale);
        $newHeight = (int) \round((float) $height * $scale);

        // Use Triangle filter with blur=1 for fast, good quality downscaling
        // This is more efficient than LANCZOS for thumbnails
        $image->resizeImage($newWidth, $newHeight, \Imagick::FILTER_TRIANGLE, 1);

        return [$newWidth, $newHeight];
    }
}
