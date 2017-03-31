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

namespace OAuth2Framework\Component\Server\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use OAuth2Framework\Component\Server\Model\Client\ClientRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\TokenEndpointAuthMethod\TokenEndpointAuthMethodManager;
use Psr\Http\Message\ServerRequestInterface;

final class ClientAuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @var TokenEndpointAuthMethodManager
     */
    private $tokenEndpointAuthMethodManager;

    /**
     * @var bool
     */
    private $authenticationRequired;

    /**
     * @var ClientRepositoryInterface
     */
    private $clientRepository;

    /**
     * ClientAuthenticationMiddleware constructor.
     *
     * @param ClientRepositoryInterface      $clientRepository
     * @param TokenEndpointAuthMethodManager $tokenEndpointAuthMethodManager
     * @param bool                           $authenticationRequired
     */
    public function __construct(ClientRepositoryInterface $clientRepository, TokenEndpointAuthMethodManager $tokenEndpointAuthMethodManager, bool $authenticationRequired)
    {
        $this->clientRepository = $clientRepository;
        $this->tokenEndpointAuthMethodManager = $tokenEndpointAuthMethodManager;
        $this->authenticationRequired = $authenticationRequired;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $clientId = $this->tokenEndpointAuthMethodManager->findClientInformationInTheRequest($request, $authentication_method, $client_credentials);
        $client = null;
        if (null !== $clientId) {
            $client = $this->clientRepository->find($clientId);
        }
        if (null !== $client && false === $this->tokenEndpointAuthMethodManager->isClientAuthenticated($request, $client, $authentication_method, $client_credentials)) {
            $client = null;
        }
        if (true === $this->authenticationRequired && null === $client) {
            throw new OAuth2Exception(
                401,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_CLIENT,
                    'error_description' => 'Client authentication failed.',
                ]
            );
        }
        if (null !== $client) {
            $request = $request->withAttribute('client', $client);
        }

        return $delegate->process($request);
    }
}
