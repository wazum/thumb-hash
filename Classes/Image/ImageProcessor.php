<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Image;

interface ImageProcessor
{
    /**
     * @return array{0: int, 1: int, 2: array<int>} [width, height, pixels]
     */
    public function extractPixels(string $content): array;
}
