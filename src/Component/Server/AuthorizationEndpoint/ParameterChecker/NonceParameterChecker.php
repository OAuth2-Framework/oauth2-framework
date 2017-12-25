<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\ParameterChecker;


use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;

/**
 * Class StateParameterChecker.
 *
 * @see http://tools.ietf.org/html/rfc6749#section-3.1.2
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
            if (false !== strpos($authorization->getQueryParam('response_type'), 'id_token')) {
                Assertion::true($authorization->hasQueryParam('nonce'), 'The parameter "nonce" is mandatory.');
            }

            return $authorization;
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }
}
