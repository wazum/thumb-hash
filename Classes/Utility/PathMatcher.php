<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Utility;

final readonly class PathMatcher
{
    public static function matches(string $path, array $patterns): bool
    {
        $normalizedPath = \trim(\trim($path), '/');
        foreach ($patterns as $pattern) {
            $normalizedPattern = \trim(\trim($pattern), '/');

            if ($normalizedPattern === '') {
                continue;
            }

            if (
                $normalizedPath === $normalizedPattern
                || \str_starts_with($normalizedPath . '/', $normalizedPattern . '/')
            ) {
                return true;
            }
        }

        return false;
    }
}
