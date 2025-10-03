<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Image;

final class GdImageProcessor implements ImageProcessor
{
    private const MAX_DIMENSION = 5000;
    private const MAX_TOTAL_PIXELS = 25_000_000;

    #[\Override]
    public function extractPixels(string $content): array
    {
        $imageInfo = @\getimagesizefromstring($content);
        if ($imageInfo === false) {
            throw new \RuntimeException('Failed to read image dimensions');
        }

        [$width, $height] = $imageInfo;
        $totalPixels = $width * $height;

        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            throw new \RuntimeException(\sprintf('Image dimensions too large: %dx%d (max %d per side)', $width, $height, self::MAX_DIMENSION));
        }

        if ($totalPixels > self::MAX_TOTAL_PIXELS) {
            throw new \RuntimeException(\sprintf('Image pixel count too large: %d (max %d)', $totalPixels, self::MAX_TOTAL_PIXELS));
        }

        $image = @\imagecreatefromstring($content);
        if ($image === false) {
            throw new \RuntimeException('Failed to create image from content');
        }

        $width = \imagesx($image);
        $height = \imagesy($image);

        // Resize immediately after loading to reduce memory pressure
        // Unlike Imagick's setSize() hint, GD must load the full image first,
        // but we minimize memory usage by resizing before pixel extraction
        [$image, $width, $height] = $this->resizeOnDemand($image, $width, $height);

        $totalPixels = $width * $height * 4;
        $pixels = new \SplFixedArray($totalPixels);
        $index = 0;
        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                $color_index = \imagecolorat($image, $x, $y);
                if ($color_index === false) {
                    throw new \RuntimeException('Failed to get color at position');
                }
                $color = \imagecolorsforindex($image, $color_index);
                $alpha = 255 - (int) \ceil($color['alpha'] * (255 / 127));

                $pixels[$index++] = $color['red'];
                $pixels[$index++] = $color['green'];
                $pixels[$index++] = $color['blue'];
                $pixels[$index++] = $alpha;
            }
        }

        $pixels = $pixels->toArray();
        \imagedestroy($image);

        return [$width, $height, $pixels];
    }

    /**
     * @return array{\GdImage, int, int}
     */
    private function resizeOnDemand(\GdImage $image, int $width, int $height): array
    {
        if ($width <= 100 && $height <= 100) {
            return [$image, $width, $height];
        }

        $scale = \min(100.0 / (float) $width, 100.0 / (float) $height);
        $newWidth = (int) \round((float) $width * $scale);
        $newHeight = (int) \round((float) $height * $scale);

        $resized = \imagecreatetruecolor($newWidth, $newHeight);
        if ($resized === false) {
            throw new \RuntimeException('Failed to create resized image');
        }

        // Preserve alpha channel during resize
        \imagealphablending($resized, false);
        \imagesavealpha($resized, true);
        $transparent = \imagecolorallocatealpha($resized, 0, 0, 0, 127);
        if ($transparent !== false) {
            \imagefill($resized, 0, 0, $transparent);
        }

        \imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        \imagedestroy($image);

        return [$resized, $newWidth, $newHeight];
    }
}
