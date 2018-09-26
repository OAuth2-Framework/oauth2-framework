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

namespace OAuth2Framework\ServerBundle\Controller;

use Http\Message\ResponseFactory;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\LoginHandler as LoginHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This feature is not yet supported so always continue the process.
 */
final class LoginHandler implements LoginHandlerInterface
{
    private $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function hasBeenProcessed(string $authorizationId, AuthorizationRequest $authorizationRequest): bool
    {
        return true;
    }

    public function isValid(string $authorizationId, AuthorizationRequest $authorizationRequest): bool
    {
        return true;
    }

    public function process(string $authorizationId, AuthorizationRequest $authorizationRequest): ResponseInterface
    {
        return $this->responseFactory->createResponse(200);
    }
}
