<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Command;

use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Wazum\ThumbHash\Configuration\ThumbHashConfiguration;
use Wazum\ThumbHash\Service\FileMetadataService;
use Wazum\ThumbHash\Service\ProcessedFileMetadataService;
use Wazum\ThumbHash\Service\ThumbHashGenerator;
use Wazum\ThumbHash\Utility\PathMatcher;

/**
 * @psalm-api
 */
final class GenerateHashesCommand extends Command
{
    public function __construct(
        private readonly ThumbHashGenerator $thumbHashGenerator,
        private readonly FileMetadataService $fileMetadataService,
        private readonly ProcessedFileMetadataService $processedFileMetadataService,
        private readonly ThumbHashConfiguration $configuration,
        private readonly ResourceFactory $resourceFactory,
        private readonly ProcessedFileRepository $processedFileRepository,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Generate ThumbHash values for files');
        $this->addOption(
            'limit',
            'l',
            InputOption::VALUE_REQUIRED,
            'Maximum number of files to process',
            '100'
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');

        $io->title('Generating ThumbHash values');

        $originalCount = $this->processOriginalFiles($io, $limit);
        $processedCount = $this->processProcessedFiles($io, $limit - $originalCount);

        $total = $originalCount + $processedCount;
        if ($total === 0) {
            $io->success('No files needed processing.');
        } else {
            $io->success(\sprintf('Generated ThumbHash for %d file(s).', $total));
        }

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    private function processOriginalFiles(SymfonyStyle $io, int $limit): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');

        $query = $queryBuilder
            ->select('f.uid')
            ->from('sys_file', 'f')
            ->leftJoin('f', 'sys_file_metadata', 'm', 'f.uid = m.file')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('m.thumb_hash', $queryBuilder->createNamedParameter(''))
                )
            );

        $allowedMimeTypes = $this->configuration->getAllowedMimeTypes();
        $query->andWhere(
            $queryBuilder->expr()->in(
                'f.mime_type',
                $queryBuilder->createNamedParameter($allowedMimeTypes, Connection::PARAM_STR_ARRAY)
            )
        );

        $query->setMaxResults($limit);

        $result = $query->executeQuery();
        $count = 0;

        while ($row = $result->fetchAssociative()) {
            try {
                $file = $this->resourceFactory->getFileObject($row['uid']);

                if (PathMatcher::matches($file->getIdentifier(), $this->configuration->getExcludedFolders())) {
                    continue;
                }

                $hash = $this->thumbHashGenerator->generateFromFile($file);
                if ($hash !== null) {
                    $this->fileMetadataService->storeHash($file, $hash);
                    ++$count;
                    $io->writeln(\sprintf('Generated hash for: %s', $file->getIdentifier()));
                }
            } catch (\Exception $e) {
                $io->warning(\sprintf('Failed to process file %d: %s', $row['uid'], $e->getMessage()));
            }
        }

        return $count;
    }

    private function processProcessedFiles(SymfonyStyle $io, int $limit): int
    {
        if ($limit <= 0) {
            return 0;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_processedfile');

        $query = $queryBuilder
            ->select('p.uid', 'p.original')
            ->from('sys_file_processedfile', 'p')
            ->leftJoin('p', 'sys_file', 'f', 'p.original = f.uid')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('p.thumb_hash', $queryBuilder->createNamedParameter(''))
                )
            );

        $query->andWhere(
            $queryBuilder->expr()->like('f.mime_type', $queryBuilder->createNamedParameter('image/%'))
        );

        $query->setMaxResults($limit);

        $result = $query->executeQuery();
        $count = 0;

        while ($row = $result->fetchAssociative()) {
            try {
                $processedFile = $this->processedFileRepository->findByUid($row['uid']);
                if (!$processedFile->usesOriginalFile()) {
                    $hash = $this->thumbHashGenerator->generateFromFile($processedFile);
                    if ($hash !== null) {
                        $this->processedFileMetadataService->storeHash($processedFile, $hash);
                        ++$count;
                        $io->writeln(\sprintf('Generated hash for processed: %s', $processedFile->getIdentifier()));
                    }
                }
            } catch (\Exception $e) {
                $io->warning(\sprintf('Failed to process processed file %d: %s', $row['uid'], $e->getMessage()));
            }
        }

        return $count;
    }
}
