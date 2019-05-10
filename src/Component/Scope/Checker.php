<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Scope;

class Checker
{
    public static function checkUsedOnce(string $scope, string $scopes): void
    {
        $scopes = \explode(' ', $scopes);
        if (1 < \count(\array_keys($scopes, $scope, true))) {
            throw new \InvalidArgumentException(\Safe\sprintf('Scope "%s" appears more than once.', $scope));
        }
    }

    public static function checkCharset(string $scope): void
    {
        if (1 !== \Safe\preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $scope)) {
            throw new \InvalidArgumentException('Scope contains illegal characters.');
        }
    }
}
