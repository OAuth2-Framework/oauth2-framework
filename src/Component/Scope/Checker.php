<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Scope;

class Checker
{
    /**
     * @param string $scope
     * @param string $scopes
     *
     * @throws \InvalidArgumentException
     */
    public static function checkUsedOnce(string $scope, string $scopes)
    {
        $scopes = explode(' ', $scopes);
        if (1 < count(array_keys($scopes, $scope))) {
            throw new \InvalidArgumentException(sprintf('Scope "%s" appears more than once.', $scope));
        }
    }

    /**
     * @param string $scope
     *
     * @throws \InvalidArgumentException
     */
    public static function checkCharset(string $scope)
    {
        if (1 !== preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $scope)) {
            throw new \InvalidArgumentException('Scope contains illegal characters.');
        }
    }
}
