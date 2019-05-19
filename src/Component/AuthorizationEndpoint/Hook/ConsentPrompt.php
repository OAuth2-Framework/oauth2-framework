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
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ConsentPrompt implements AuthorizationEndpointHook
{
    /**
     * @var ConsentHandler
     */
    private $consentHandler;

    public function __construct(ConsentHandler $consentHandler)
    {
        $this->consentHandler = $consentHandler;
    }

    public function handle(ServerRequestInterface $request, AuthorizationRequest $authorizationRequest, string $authorizationRequestId): ?ResponseInterface
    {
        if (!$authorizationRequest->hasPrompt('consent')) {
            return null;
        }

        if ($authorizationRequest->hasConsentBeenGiven()) {
            return null;
        }

        return $this->consentHandler->process($request, $authorizationRequestId, $authorizationRequest);
    }
}
