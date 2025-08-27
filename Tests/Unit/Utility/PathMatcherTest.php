<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Wazum\ThumbHash\Utility\PathMatcher;

final class PathMatcherTest extends TestCase
{
    #[DataProvider('matchesDataProvider')]
    public function testMatches(string $path, array $patterns, bool $expected): void
    {
        self::assertSame($expected, PathMatcher::matches($path, $patterns));
    }

    public static function matchesDataProvider(): array
    {
        return [
            'empty patterns' => [
                'fileadmin/images',
                [],
                false,
            ],
            'no match' => [
                'fileadmin/images',
                ['fileadmin/_processed_', 'fileadmin/_temp_'],
                false,
            ],
            'exact folder match' => [
                'fileadmin/_processed_',
                ['fileadmin/_processed_'],
                true,
            ],
            'subfolder match' => [
                'fileadmin/_processed_/subfolder',
                ['fileadmin/_processed_'],
                true,
            ],
            'nested deep subfolder match' => [
                'fileadmin/_processed_/a/b/c/d',
                ['fileadmin/_processed_'],
                true,
            ],
            'path with leading slash matches pattern without' => [
                '/fileadmin/_processed_',
                ['fileadmin/_processed_'],
                true,
            ],
            'pattern with trailing slash matches path without' => [
                'fileadmin/_processed_',
                ['fileadmin/_processed_/'],
                true,
            ],
            'pattern with spaces is trimmed' => [
                'fileadmin/_processed_',
                [' fileadmin/_processed_ '],
                true,
            ],
            'empty patterns are ignored' => [
                'fileadmin/images',
                ['', ' '],
                false,
            ],
            'multiple patterns with match' => [
                'fileadmin/_temp_',
                ['fileadmin/_processed_', 'fileadmin/_temp_', 'uploads'],
                true,
            ],
            'partial folder name does not match' => [
                'fileadmin_processed',
                ['fileadmin/_processed_'],
                false,
            ],
            'folder boundary is respected' => [
                'fileadmin/temp_files',
                ['fileadmin/temp'],
                false,
            ],
            'case sensitive' => [
                'fileadmin/_Processed_',
                ['fileadmin/_processed_'],
                false,
            ],
        ];
    }
}
