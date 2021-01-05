<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\Hook;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\SelectAccountHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SelectAccountPrompt implements AuthorizationEndpointHook
{
    private SelectAccountHandler $selectAccountHandler;

    public function __construct(SelectAccountHandler $selectAccountHandler)
    {
        $this->selectAccountHandler = $selectAccountHandler;
    }

    public function handle(ServerRequestInterface $request, string $authorizationRequestId, AuthorizationRequest $authorizationRequest): ?ResponseInterface
    {
        if (!$authorizationRequest->hasPrompt('select_account')) {
            return null;
        }

        if ($authorizationRequest->hasAttribute('account_has_been_selected') && true === $authorizationRequest->getAttribute('account_has_been_selected')) {
            return null;
        }

        return $this->selectAccountHandler->handle($request, $authorizationRequestId);
    }
}
