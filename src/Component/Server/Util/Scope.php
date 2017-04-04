<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Util;

use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;

final class Scope
{
    /**
     * @param string $scope
     * @param array  $scopes
     *
     * @throws OAuth2Exception
     */
    public static function checkScopeUsedOnce(string $scope, array $scopes)
    {
        if (1 < count(array_keys($scopes, $scope))) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_SCOPE, 'error_description' => sprintf('Scope \'%s\' appears more than once.', $scope)]);
        }
    }

    /**
     * @param string $scope
     *
     * @throws OAuth2Exception
     */
    public static function checkScopeCharset(string $scope)
    {
        if (1 !== preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $scope)) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_SCOPE, 'error_description' => 'Scope contains illegal characters.']);
        }
    }
}
