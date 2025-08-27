<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Service;

use Thumbhash\Thumbhash;
use TYPO3\CMS\Core\Resource\FileInterface;
use Wazum\ThumbHash\Image\ImageProcessor;
use Wazum\ThumbHash\Image\ImageProcessorFactory;

final readonly class ThumbHashGenerator
{
    private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB

    private ImageProcessor $imageProcessor;

    public function __construct(ImageProcessorFactory $processorFactory)
    {
        $this->imageProcessor = $processorFactory->create();
    }

    public function generateFromFile(FileInterface $file): ?string
    {
        try {
            $this->validateFile($file);
            $content = $file->getContents();

            return empty($content) ? null : $this->generateFromContent($content);
        } catch (\Throwable) {
            return null;
        }
    }

    private function validateFile(FileInterface $file): void
    {
        $size = $file->getSize();
        if ($size > 0 && $size > self::MAX_FILE_SIZE) {
            throw new \RuntimeException('File too large for placeholder generation (max 50MB)');
        }
    }

    private function generateFromContent(string $content): string
    {
        [$width, $height, $pixels] = $this->imageProcessor->extractPixels($content);

        return Thumbhash::convertHashToString(
            Thumbhash::RGBAToHash($width, $height, $pixels)
        );
    }
}
