<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Unit\ViewHelpers;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use Wazum\ThumbHash\Configuration\ThumbHashConfiguration;
use Wazum\ThumbHash\Image\ImageProcessorFactory;
use Wazum\ThumbHash\Service\FileMetadataService;
use Wazum\ThumbHash\Service\ProcessedFileMetadataService;
use Wazum\ThumbHash\Service\ThumbHashGenerator;
use Wazum\ThumbHash\ViewHelpers\ThumbHashViewHelper;

final class ThumbHashViewHelperTest extends TestCase
{
    private ThumbHashGenerator $generator;
    private FileMetadataService $fileMetadataService;
    private ProcessedFileMetadataService $processedFileMetadataService;
    private ThumbHashViewHelper $viewHelper;

    #[\Override]
    protected function setUp(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn([
            'imageProcessor' => 'gd',
        ]);
        $configuration = new ThumbHashConfiguration($extensionConfiguration);
        $processorFactory = new ImageProcessorFactory($configuration);
        $this->generator = new ThumbHashGenerator($processorFactory);

        // Create a real metadata service with mocked DB connection
        $connectionPool = $this->createMock(ConnectionPool::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $connection = $this->createMock(Connection::class);
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $result = $this->createMock(Result::class);

        $connectionPool->method('getQueryBuilderForTable')->willReturn($queryBuilder);
        $connectionPool->method('getConnectionForTable')->willReturn($connection);

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('executeQuery')->willReturn($result);
        $queryBuilder->method('createNamedParameter')->willReturn('?');
        $queryBuilder->method('expr')->willReturn($expressionBuilder);

        $result->method('fetchOne')->willReturn(false);
        $expressionBuilder->method('eq')->willReturn('file = ?');

        $this->fileMetadataService = new FileMetadataService($connectionPool);
        $this->processedFileMetadataService = new ProcessedFileMetadataService($connectionPool);
        $this->viewHelper = new ThumbHashViewHelper(
            $this->generator,
            $this->fileMetadataService,
            $this->processedFileMetadataService
        );
    }

    #[Test]
    public function generatesHashForFileObject(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->method('getContents')
            ->willReturn(\file_get_contents(__DIR__ . '/../../Fixtures/lightning-strikes.jpg'));
        $fileMock->method('getUid')->willReturn(123);

        $this->viewHelper->setArguments(['file' => $fileMock]);

        $result = $this->viewHelper->render();

        $this->assertSame('E/cNFYJWaHeMh3eAeHh3eWaAWFMJ', $result);
    }

    #[Test]
    public function generatesHashForFileReferenceObject(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock->method('getContents')
            ->willReturn(\file_get_contents(__DIR__ . '/../../Fixtures/lightning-strikes.jpg'));
        $fileMock->method('getUid')->willReturn(123);

        $fileReferenceMock = $this->createMock(FileReference::class);
        $fileReferenceMock->method('getOriginalFile')->willReturn($fileMock);

        $this->viewHelper->setArguments(['file' => $fileReferenceMock]);

        $result = $this->viewHelper->render();

        $this->assertSame('E/cNFYJWaHeMh3eAeHh3eWaAWFMJ', $result);
    }

    #[Test]
    public function generatesHashForProcessedFile(): void
    {
        $processedFileMock = $this->createMock(ProcessedFile::class);
        $processedFileMock->method('getContents')
            ->willReturn(\file_get_contents(__DIR__ . '/../../Fixtures/lightning-strikes.jpg'));

        $this->viewHelper->setArguments(['file' => $processedFileMock]);

        $result = $this->viewHelper->render();

        $this->assertSame('E/cNFYJWaHeMh3eAeHh3eWaAWFMJ', $result);
    }

    #[Test]
    public function returnsEmptyStringForInvalidFile(): void
    {
        $this->viewHelper->setArguments(['file' => null]);

        $result = $this->viewHelper->render();

        $this->assertSame('', $result);
    }
}
