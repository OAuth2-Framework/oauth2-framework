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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Hook;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class ConsentPrompt implements AuthorizationEndpointHook
{
    public function handle(ServerRequestInterface $request, AuthorizationRequest $authorizationRequest, string $authorizationRequestId): ?ResponseInterface
    {
        if (!$authorizationRequest->hasPrompt('consent')) {
            return null;
        }

        if ($authorizationRequest->hasConsentBeenGiven()) {
            return null;
        }

        return $this->processWithConsentResponse($request, $authorizationRequestId, $authorizationRequest);
    }

    abstract protected function processWithConsentResponse(ServerRequestInterface $request, string $authorizationRequestId, AuthorizationRequest $authorizationRequest): ?ResponseInterface;
}
