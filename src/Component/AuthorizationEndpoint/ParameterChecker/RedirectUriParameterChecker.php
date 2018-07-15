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

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Message\OAuth2Message;

final class RedirectUriParameterChecker implements ParameterChecker
{
    /**
     * {@inheritdoc}
     */
    public function check(Authorization $authorization): Authorization
    {
        try {
            if (!$authorization->hasQueryParam('redirect_uri')) {
                throw new \InvalidArgumentException('The parameter "redirect_uri" is mandatory.');
            }
            $redirectUri = $authorization->getQueryParam('redirect_uri');
            $availableRedirectUris = $this->getRedirectUris($authorization);
            if (!empty($availableRedirectUris) && !\in_array($redirectUri, $availableRedirectUris, true)) {
                throw new \InvalidArgumentException(\sprintf('The redirect URI "%s" is not registered.', $redirectUri));
            }

            $authorization = $authorization->withRedirectUri($redirectUri);

            return $authorization;
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2AuthorizationException(400, OAuth2Message::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }

    /**
     * @param Authorization $authorization
     *
     * @return string[]
     */
    private function getRedirectUris(Authorization $authorization): array
    {
        return $authorization->getClient()->has('redirect_uris') ? $authorization->getClient()->get('redirect_uris') : [];
    }
}
