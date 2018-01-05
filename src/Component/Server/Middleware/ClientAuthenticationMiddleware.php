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

namespace OAuth2Framework\Component\Server\Middleware;

use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\TokenEndpoint\AuthenticationMethod\TokenEndpointAuthenticationMethodManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ClientAuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @var TokenEndpointAuthenticationMethodManager
     */
    private $tokenEndpointAuthenticationMethodManager;

    /**
     * @var bool
     */
    private $authenticationRequired;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * ClientAuthenticationMiddleware constructor.
     *
     * @param ClientRepository               $clientRepository
     * @param TokenEndpointAuthenticationMethodManager $tokenEndpointAuthenticationMethodManager
     * @param bool                           $authenticationRequired
     */
    public function __construct(ClientRepository $clientRepository, TokenEndpointAuthenticationMethodManager $tokenEndpointAuthenticationMethodManager, bool $authenticationRequired)
    {
        $this->clientRepository = $clientRepository;
        $this->tokenEndpointAuthenticationMethodManager = $tokenEndpointAuthenticationMethodManager;
        $this->authenticationRequired = $authenticationRequired;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientId = $this->tokenEndpointAuthenticationMethodManager->findClientInformationInTheRequest($request, $authentication_method, $client_credentials);
        $client = null;
        if (null !== $clientId) {
            $client = $this->clientRepository->find($clientId);
        }
        if (null !== $client && false === $this->tokenEndpointAuthenticationMethodManager->isClientAuthenticated($request, $client, $authentication_method, $client_credentials)) {
            $client = null;
        }
        if (true === $this->authenticationRequired && null === $client) {
            throw new OAuth2Exception(
                401,
                OAuth2Exception::ERROR_INVALID_CLIENT,
                'Client authentication failed.'
            );
        }
        if (null !== $client) {
            $request = $request->withAttribute('client', $client);
        }

        return $handler->handle($request);
    }
}
