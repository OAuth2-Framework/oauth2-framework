<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope;

use function count;
use InvalidArgumentException;

class Checker
{
    public static function checkUsedOnce(string $scope, string $scopes): void
    {
        $scopes = explode(' ', $scopes);
        if (count(array_keys($scopes, $scope, true)) > 1) {
            throw new InvalidArgumentException(sprintf('Scope "%s" appears more than once.', $scope));
        }
    }

    public static function checkCharset(string $scope): void
    {
        if (preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $scope) !== 1) {
            throw new InvalidArgumentException('Scope contains illegal characters.');
        }
    }
}
