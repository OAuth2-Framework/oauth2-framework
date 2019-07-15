<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\SecurityBundle\Security\Firewall;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandlerManager;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

final class OAuth2Listener
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @var TokenTypeManager
     */
    private $tokenTypeManager;

    /**
     * @var AccessTokenHandlerManager
     */
    private $accessTokenHandlerManager;

    /**
     * @var OAuth2MessageFactoryManager
     */
    private $oauth2ResponseFactoryManager;

    /**
     * @var HttpMessageFactoryInterface
     */
    private $httpMessageFactory;

    public function __construct(
        HttpMessageFactoryInterface $httpMessageFactory,
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        TokenTypeManager $tokenTypeManager,
        AccessTokenHandlerManager $accessTokenHandlerManager,
        OAuth2MessageFactoryManager $oauth2ResponseFactoryManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->tokenTypeManager = $tokenTypeManager;
        $this->accessTokenHandlerManager = $accessTokenHandlerManager;
        $this->oauth2ResponseFactoryManager = $oauth2ResponseFactoryManager;
        $this->httpMessageFactory = $httpMessageFactory;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $this->httpMessageFactory->createRequest($event->getRequest());

        try {
            $additionalCredentialValues = [];
            $accessTokenId = $this->tokenTypeManager->findToken($request, $additionalCredentialValues, $tokenType);
            if (null === $accessTokenId) {
                return;
            }
            // @var TokenType $tokenType
        } catch (Throwable $e) {
            return;
        }

        try {
            $accessToken = $this->accessTokenHandlerManager->find(new AccessTokenId($accessTokenId));
            if (null === $accessToken || $accessToken->isRevoked()) {
                throw new AuthenticationException('Invalid access token.');
            }
            if ($accessToken->hasExpired()) {
                throw new AuthenticationException('The access token expired.');
            }
            if (!$tokenType->isRequestValid($accessToken, $request, $additionalCredentialValues)) {
                throw new AuthenticationException('Invalid access token.');
            }

            $token = new OAuth2Token($accessToken);
            $result = $this->authenticationManager->authenticate($token);

            $this->tokenStorage->setToken($result);
        } catch (AuthenticationException $e) {
            $psr7Response = $this->oauth2ResponseFactoryManager->getResponse(
                OAuth2Error::accessDenied('OAuth2 authentication required. '.$e->getMessage(), [], $e)
            );
            $factory = new HttpFoundationFactory();
            $event->setResponse($factory->createResponse($psr7Response));
        }
    }
}
