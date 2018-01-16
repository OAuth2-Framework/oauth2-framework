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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;

final class RedirectUriParameterChecker implements ParameterChecker
{
    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization, callable $next): Authorization
    {
        try {
            if (!$authorization->hasQueryParam('redirect_uri')) {
                throw new \InvalidArgumentException('The parameter "redirect_uri" is mandatory.');
            }
            $redirectUri = $authorization->getQueryParam('redirect_uri');
            $client_redirect_uris = $authorization->getClient()->has('redirect_uris') ? $authorization->getClient()->get('redirect_uris') : [];

            if (!in_array($redirectUri, $client_redirect_uris)) {
                throw new \InvalidArgumentException('The specified redirect URI is not valid.');
            }
            $authorization = $authorization->withRedirectUri($redirectUri);

            return $next($authorization);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }
}
