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

namespace OAuth2Framework\Bundle\Server\Controller;

use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\Client\ClientRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class ClientConfigurationMiddleware implements MiddlewareInterface
{
    /**
     * @var ClientRepositoryInterface
     */
    private $clientRepository;

    /**
     * ClientConfigurationMiddleware constructor.
     *
     * @param ClientRepositoryInterface $clientRepository
     */
    public function __construct(ClientRepositoryInterface $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeParameters = $request->getAttribute('_route_params');
        if (!is_array($routeParameters) || !array_key_exists('client_id', $routeParameters) || null === $client = $this->clientRepository->find(ClientId::create($routeParameters['client_id']))) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => 'Invalid client or invalid registration access token.']);
        }
        $request = $request->withAttribute('client', $client);

        return $handler->handle($request);
    }
}
