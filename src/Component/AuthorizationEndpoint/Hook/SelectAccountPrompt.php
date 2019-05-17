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

abstract class SelectAccountPrompt implements AuthorizationEndpointHook
{
    public function handle(ServerRequestInterface $request, AuthorizationRequest $authorizationRequest, string $authorizationRequestId): ?ResponseInterface
    {
        if (!$authorizationRequest->hasPrompt('select_account')) {
            return null;
        }

        if ($authorizationRequest->hasAttribute('account_has_been_selected') && true === $authorizationRequest->getAttribute('account_has_been_selected')) {
            return null;
        }

        return $this->processWithAccountSelectionResponse($request, $authorizationRequestId, $authorizationRequest);
    }

    abstract protected function processWithAccountSelectionResponse(ServerRequestInterface $request, string $authorizationRequestId, AuthorizationRequest $authorizationRequest): ?ResponseInterface;
}
