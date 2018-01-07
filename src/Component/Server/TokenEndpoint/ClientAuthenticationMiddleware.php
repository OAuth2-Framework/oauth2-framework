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

namespace OAuth2Framework\Component\Server\TokenEndpoint;

use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\TokenEndpoint\AuthenticationMethod\AuthenticationMethodManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ClientAuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @var AuthenticationMethodManager
     */
    private $authenticationMethodManager;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * ClientAuthenticationMiddleware constructor.
     *
     * @param ClientRepository            $clientRepository
     * @param AuthenticationMethodManager $authenticationMethodManager
     */
    public function __construct(ClientRepository $clientRepository, AuthenticationMethodManager $authenticationMethodManager)
    {
        $this->clientRepository = $clientRepository;
        $this->authenticationMethodManager = $authenticationMethodManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientId = $this->authenticationMethodManager->findClientIdAndCredentials($request, $authentication_method, $client_credentials);
        if (null !== $clientId) {
            $client = $this->clientRepository->find($clientId);
            if ($this->authenticationMethodManager->isClientAuthenticated($request, $client, $authentication_method, $client_credentials)) {
                $request = $request->withAttribute('client', $client);
            }
        }

        return $handler->handle($request);
    }
}
