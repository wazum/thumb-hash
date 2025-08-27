<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Configuration;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final readonly class ThumbHashConfiguration
{
    private array $configuration;

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $this->configuration = $extensionConfiguration->get('thumb_hash') ?? [];
    }

    public function isAutoGenerateEnabled(): bool
    {
        return (bool) ($this->configuration['autoGenerate'] ?? true);
    }

    public function getAllowedMimeTypes(): array
    {
        $mimeTypes = $this->configuration['allowedMimeTypes'] ?? 'image/jpeg,image/png,image/gif';

        return \array_map(trim(...), \explode(',', $mimeTypes));
    }

    public function getExcludedFolders(): array
    {
        $folders = $this->configuration['excludedFolders'] ?? 'fileadmin/_processed_/,fileadmin/_temp_/';

        return \array_map(trim(...), \explode(',', $folders));
    }
}
