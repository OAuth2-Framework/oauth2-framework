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

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use Assert\Assertion;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

final class RedirectUriParameterChecker implements ParameterChecker
{
    public function check(AuthorizationRequest $authorization): void
    {
        Assertion::true($authorization->hasQueryParam('redirect_uri'), 'The parameter "redirect_uri" is mandatory.');
        $redirectUri = $authorization->getQueryParam('redirect_uri');
        $availableRedirectUris = $this->getRedirectUris($authorization);
        if (0 < \count($availableRedirectUris)) {
            Assertion::inArray($redirectUri, $availableRedirectUris, \Safe\sprintf('The redirect URI "%s" is not registered.', $redirectUri));
        }

        $authorization->setRedirectUri($redirectUri);
    }

    /**
     * @return string[]
     */
    private function getRedirectUris(AuthorizationRequest $authorization): array
    {
        return $authorization->getClient()->has('redirect_uris') ? $authorization->getClient()->get('redirect_uris') : [];
    }
}
