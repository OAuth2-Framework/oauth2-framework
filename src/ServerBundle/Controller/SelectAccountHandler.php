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

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\SelectAccountHandler as SelectAccountHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This feature is not yet supported so always continue the process.
 */
final class SelectAccountHandler implements SelectAccountHandlerInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function prepare(ServerRequestInterface $serverRequest, string $authorizationId, AuthorizationRequest $authorizationRequest): void
    {
        // Nothing to do
    }

    public function hasBeenProcessed(ServerRequestInterface $serverRequest, string $authorizationId, AuthorizationRequest $authorizationRequest): bool
    {
        return true;
    }

    public function isValid(ServerRequestInterface $serverRequest, string $authorizationId, AuthorizationRequest $authorizationRequest): bool
    {
        return true;
    }

    public function process(ServerRequestInterface $serverRequest, string $authorizationId, AuthorizationRequest $authorizationRequest): ResponseInterface
    {
        return $this->responseFactory->createResponse(200);
    }
}
