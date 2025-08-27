<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Service;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;

final readonly class FileMetadataService
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getHash(File $file): ?string
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_metadata');
        $result = $queryBuilder
            ->select('thumb_hash')
            ->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq(
                    'file',
                    $queryBuilder->createNamedParameter($file->getUid(), ParameterType::INTEGER)
                )
            )
            ->executeQuery()
            ->fetchOne();

        return match ($result) {
            false, '' => null,
            default => (string) $result,
        };
    }

    /**
     * @throws Exception
     */
    public function storeHash(File $file, string $hash): void
    {
        if ($this->getHash($file) === $hash) {
            return;
        }

        $this->connectionPool
            ->getConnectionForTable('sys_file_metadata')
            ->update(
                'sys_file_metadata',
                ['thumb_hash' => $hash],
                ['file' => $file->getUid()]
            );
    }
}
