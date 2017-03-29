<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Endpoint\JWKSet;

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Jose\Object\JWKSetInterface;
use Psr\Http\Message\ServerRequestInterface;

final class JWKSetEndpoint implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var JWKSetInterface
     */
    private $JWKSet;

    /**
     * JWKSetEndpoint constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param JWKSetInterface          $JWKSet
     */
    public function __construct(ResponseFactoryInterface $responseFactory, JWKSetInterface $JWKSet)
    {
        $this->responseFactory = $responseFactory;
        $this->JWKSet = $JWKSet;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(json_encode($this->JWKSet));
        $response = $response->withHeader('Content-Type', 'application/jwk-set+json; charset=UTF-8');

        return $response;
    }
}
