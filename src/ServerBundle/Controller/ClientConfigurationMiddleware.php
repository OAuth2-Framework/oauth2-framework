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

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ClientConfigurationMiddleware implements MiddlewareInterface
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * ClientConfigurationMiddleware constructor.
     *
     * @param ClientRepository $clientRepository
     */
    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeParameters = $request->getAttribute('_route_params');
        if (!\is_array($routeParameters) || !\array_key_exists('client_id', $routeParameters) || null === $client = $this->clientRepository->find(ClientId::create($routeParameters['client_id']))) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_REQUEST, 'Invalid client or invalid registration access token.');
        }
        $request = $request->withAttribute('client', $client);

        return $handler->handle($request);
    }
}
