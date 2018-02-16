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

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        try {
            $clientId = $this->authenticationMethodManager->findClientIdAndCredentials($request, $authentication_method, $client_credentials);
            if (null !== $clientId) {
                $client = $this->clientRepository->find($clientId);
                $this->checkClient($client);
                $this->checkAuthenticationMethod($request, $client, $authentication_method, $client_credentials);
                $request = $request->withAttribute('client', $client);
                $request = $request->withAttribute('client_authentication_method', $authentication_method);
                $request = $request->withAttribute('client_credentials', $client_credentials);
            }
        } catch (\Exception $e) {
            throw new OAuth2Exception(401, OAuth2Exception::ERROR_INVALID_CLIENT, $e->getMessage(), $e);
        }

        return $handler->handle($request);
    }

    /**
     * @param null|Client $client
     */
    private function checkClient(?Client $client)
    {
        if (null === $client || $client->isDeleted()) {
            throw new \InvalidArgumentException('Client authentication failed.');
        }
        if ($client->areClientCredentialsExpired()) {
            throw new \InvalidArgumentException('Client credentials expired.');
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param Client                 $client
     * @param AuthenticationMethod   $authenticationMethod
     * @param mixed                  $client_credentials
     */
    private function checkAuthenticationMethod(ServerRequestInterface $request, Client $client, AuthenticationMethod $authenticationMethod, $client_credentials)
    {
        if (!$client->has('token_endpoint_auth_method') || !in_array($client->get('token_endpoint_auth_method'), $authenticationMethod->getSupportedMethods())) {
            throw new \InvalidArgumentException('Client authentication failed.');
        }
        if (!$authenticationMethod->isClientAuthenticated($client, $client_credentials, $request)) {
            throw new \InvalidArgumentException('Client authentication failed.');
        }
    }
}
