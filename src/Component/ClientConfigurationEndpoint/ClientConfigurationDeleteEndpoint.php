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

namespace OAuth2Framework\Component\ClientConfigurationEndpoint;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use Psr\Http\Message\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ClientConfigurationDeleteEndpoint implements MiddlewareInterface
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(ClientRepository $clientRepository, ResponseFactory $responseFactory)
    {
        $this->clientRepository = $clientRepository;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        /** @var Client $client */
        $client = $request->getAttribute('client');
        $client->markAsDeleted();
        $this->clientRepository->save($client);

        $response = $this->responseFactory->createResponse(
            204,
            null,
            [
                'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private',
                'Pragma' => 'no-cache',
            ]
        );

        return $response;
    }
}
