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

namespace OAuth2Framework\Component\Server\OpenIdConnect\AfterConsentScreen;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\AfterConsentScreen\AfterConsentScreen;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use Psr\Http\Message\ServerRequestInterface;

abstract class SessionStateParameterExtension implements AfterConsentScreen
{
    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        if ($this->hasOpenIdScope($authorization)) {
            $browserState = $this->getBrowserState($request, $authorization);
            $sessionState = $this->calculateSessionState($request, $authorization, $browserState);
            $authorization = $authorization->withResponseParameter('session_state', $sessionState);
        }

        return $authorization;
    }

    /**
     * @param Authorization $authorization
     *
     * @return bool
     */
    private function hasOpenIdScope(Authorization $authorization): bool
    {
        if (!$authorization->hasQueryParam('scope')) {
            return false;
        }

        $scope = $authorization->hasQueryParam('scope');
        $scopes = explode(' ', $scope);

        return in_array('openid', $scopes);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     *
     * @return string
     */
    abstract protected function getBrowserState(ServerRequestInterface $request, Authorization &$authorization): string;

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     * @param string                 $browserState
     *
     * @return string
     */
    abstract protected function calculateSessionState(ServerRequestInterface $request, Authorization $authorization, string $browserState): string;
}
