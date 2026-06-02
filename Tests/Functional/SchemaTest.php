<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SchemaTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wazum/thumb-hash',
    ];

    #[Test]
    public function thumbHashColumnIsAddedToFileMetadata(): void
    {
        self::assertTrue($this->columnExists('sys_file_metadata', 'thumb_hash'));
    }

    #[Test]
    public function thumbHashColumnIsAddedToProcessedFile(): void
    {
        self::assertTrue($this->columnExists('sys_file_processedfile', 'thumb_hash'));
    }

    private function columnExists(string $table, string $column): bool
    {
        $columns = $this->getConnectionPool()
            ->getConnectionForTable($table)
            ->createSchemaManager()
            ->listTableColumns($table);

        return isset($columns[$column]) || isset($columns[\strtolower($column)]);
    }
}
