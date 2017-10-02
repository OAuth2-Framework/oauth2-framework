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

use Http\Message\MessageFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use Jose\Component\Core\JWKSet;
use Psr\Http\Message\ServerRequestInterface;

final class JWKSetEndpoint implements MiddlewareInterface
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var JWKSet
     */
    private $jwkSet;

    /**
     * JWKSetEndpoint constructor.
     *
     * @param MessageFactory $messageFactory
     * @param JWKSet         $jwkSet
     */
    public function __construct(MessageFactory $messageFactory, JWKSet $jwkSet)
    {
        $this->messageFactory = $messageFactory;
        $this->jwkSet = $jwkSet;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler)
    {
        $response = $this->messageFactory->createResponse();
        $response->getBody()->write(json_encode($this->jwkSet, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $response = $response->withHeader('Content-Type', 'application/jwk-set+json; charset=UTF-8');

        return $response;
    }
}
