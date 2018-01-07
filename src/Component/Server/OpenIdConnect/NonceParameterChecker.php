<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\OpenIdConnect;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ParameterChecker\ParameterChecker;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;

/**
 * Class NonceParameterChecker.
 */
final class NonceParameterChecker implements ParameterChecker
{
    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization, callable $next): Authorization
    {
        try {
            $authorization = $next($authorization);
            if (false !== strpos($authorization->getQueryParam('response_type'), 'id_token') && !$authorization->hasQueryParam('nonce')) {
                throw new \InvalidArgumentException('The parameter "nonce" is mandatory when the response type "id_token" is used.');
            }

            return $authorization;
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }
}
