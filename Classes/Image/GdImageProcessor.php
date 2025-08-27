<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Image;

final class GdImageProcessor implements ImageProcessor
{
    #[\Override]
    public function extractPixels(string $content): array
    {
        $image = @\imagecreatefromstring($content);
        if ($image === false) {
            throw new \RuntimeException('Failed to create image from content');
        }

        $width = \imagesx($image);
        $height = \imagesy($image);

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
