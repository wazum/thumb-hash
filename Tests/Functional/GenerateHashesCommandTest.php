<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Tester\CommandTester;
use Wazum\ThumbHash\Command\GenerateHashesCommand;

final class GenerateHashesCommandTest extends AbstractFunctionalTestCase
{
    #[Test]
    public function commandGeneratesHashForIndexedOriginalFile(): void
    {
        $file = $this->provideIndexedFile();
        self::assertNull(
            $this->readHash('sys_file_metadata', 'file', (int) $file->getUid()),
            'Indexed file should start without a hash',
        );

        $commandTester = new CommandTester($this->get(GenerateHashesCommand::class));
        $exitCode = $commandTester->execute(['--limit' => '10']);

        self::assertSame(0, $exitCode);
        self::assertNotNull(
            $this->readHash('sys_file_metadata', 'file', (int) $file->getUid()),
            'Command should have generated and stored a hash',
        );
    }
}
