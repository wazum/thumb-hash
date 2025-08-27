<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Image;

final readonly class ImageProcessorFactory
{
    public function create(): ImageProcessor
    {
        if (\extension_loaded('imagick')) {
            return new ImagickImageProcessor();
        }

        return new GdImageProcessor();
    }
}
