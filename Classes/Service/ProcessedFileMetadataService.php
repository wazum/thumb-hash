<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Service;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ProcessedFile;

final readonly class ProcessedFileMetadataService
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getHash(ProcessedFile $file): ?string
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_processedfile');
        $result = $queryBuilder
            ->select('thumb_hash')
            ->from('sys_file_processedfile')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
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
    public function storeHash(ProcessedFile $file, string $hash): void
    {
        if ($this->getHash($file) === $hash) {
            return;
        }

        $this->connectionPool
            ->getConnectionForTable('sys_file_processedfile')
            ->update(
                'sys_file_processedfile',
                ['thumb_hash' => $hash],
                ['uid' => $file->getUid()]
            );
    }
}
