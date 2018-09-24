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

namespace OAuth2Framework\Component\OpenIdConnect\ConsentScreen;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\Extension;
use Psr\Http\Message\ServerRequestInterface;

abstract class SessionStateParameterExtension implements Extension
{
    public function processAfter(ServerRequestInterface $request, AuthorizationRequest $authorization): void
    {
        if ($this->hasOpenIdScope($authorization)) {
            $browserState = $this->getBrowserState($request, $authorization);
            $sessionState = $this->calculateSessionState($request, $authorization, $browserState);
            $authorization->setResponseParameter('session_state', $sessionState);
        }
    }

    private function hasOpenIdScope(AuthorizationRequest $authorization): bool
    {
        if (!$authorization->hasQueryParam('scope')) {
            return false;
        }

        $scope = $authorization->getQueryParam('scope');
        $scopes = \explode(' ', $scope);

        return \in_array('openid', $scopes, true);
    }

    abstract protected function getBrowserState(ServerRequestInterface $request, AuthorizationRequest $authorization): string;

    abstract protected function calculateSessionState(ServerRequestInterface $request, AuthorizationRequest $authorization, string $browserState): string;
}
