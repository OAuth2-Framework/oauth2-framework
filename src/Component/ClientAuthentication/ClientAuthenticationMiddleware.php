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

namespace OAuth2Framework\Component\ClientAuthentication;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ClientAuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @var AuthenticationMethodManager
     */
    private $authenticationMethodManager;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    public function __construct(ClientRepository $clientRepository, AuthenticationMethodManager $authenticationMethodManager)
    {
        $this->clientRepository = $clientRepository;
        $this->authenticationMethodManager = $authenticationMethodManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $authentication_method = null;
            $client_credentials = null;
            $clientId = $this->authenticationMethodManager->findClientIdAndCredentials($request, $authentication_method, $client_credentials);
            if (null !== $clientId && $authentication_method instanceof AuthenticationMethod) {
                $client = $this->clientRepository->find($clientId);
                $this->checkClient($client);
                $this->checkAuthenticationMethod($request, $client, $authentication_method, $client_credentials);
                $request = $request->withAttribute('client', $client);
                $request = $request->withAttribute('client_authentication_method', $authentication_method);
                $request = $request->withAttribute('client_credentials', $client_credentials);
            }
        } catch (\Exception $e) {
            throw new OAuth2Error(401, OAuth2Error::ERROR_INVALID_CLIENT, $e->getMessage(), [], $e);
        }

        return $handler->handle($request);
    }

    private function checkClient(?Client $client): void
    {
        if (null === $client || $client->isDeleted()) {
            throw new \InvalidArgumentException('Client authentication failed.');
        }
        if ($client->areClientCredentialsExpired()) {
            throw new \InvalidArgumentException('Client credentials expired.');
        }
    }

    private function checkAuthenticationMethod(ServerRequestInterface $request, Client $client, AuthenticationMethod $authenticationMethod, $client_credentials): void
    {
        if (!$client->has('token_endpoint_auth_method') || !\in_array($client->get('token_endpoint_auth_method'), $authenticationMethod->getSupportedMethods(), true)) {
            throw new \InvalidArgumentException('Client authentication failed.');
        }
        if (!$authenticationMethod->isClientAuthenticated($client, $client_credentials, $request)) {
            throw new \InvalidArgumentException('Client authentication failed.');
        }
    }
}
