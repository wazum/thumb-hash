<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Image;

use Wazum\ThumbHash\Configuration\ThumbHashConfiguration;

final readonly class ImageProcessorFactory
{
    public function __construct(
        private ThumbHashConfiguration $configuration,
    ) {
    }

    public function create(): ImageProcessor
    {
        $choice = $this->configuration->getImageProcessor();

        return match ($choice) {
            'imagick' => $this->createImagick(),
            'gd' => $this->createGd(),
            default => $this->createAuto(),
        };
    }

    private function createAuto(): ImageProcessor
    {
        if (\extension_loaded('imagick')) {
            return new ImagickImageProcessor();
        }

        return new GdImageProcessor();
    }

    private function createImagick(): ImageProcessor
    {
        if (!\extension_loaded('imagick')) {
            $message = 'Configured image processor "imagick" is not available (Imagick extension missing).';
            throw new \RuntimeException($message);
        }

        return new ImagickImageProcessor();
    }

    private function createGd(): ImageProcessor
    {
        if (!\extension_loaded('gd')) {
            throw new \RuntimeException('Configured image processor "gd" is not available (GD extension missing).');
        }

        return new GdImageProcessor();
    }
}
