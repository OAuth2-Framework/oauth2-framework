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

namespace OAuth2Framework\ServerBundle\Security\Firewall;

use OAuth2Framework\Component\Core\Message\OAuth2Message;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use OAuth2Framework\ServerBundle\Security\Authentication\Token\OAuth2Token;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandlerManager;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

final class OAuth2Listener implements ListenerInterface
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
     * OAuth2Listener constructor.
     *
     * @param TokenStorageInterface          $tokenStorage
     * @param AuthenticationManagerInterface $authenticationManager
     * @param TokenTypeManager               $tokenTypeManager
     * @param AccessTokenHandlerManager      $accessTokenHandlerManager
     * @param OAuth2MessageFactoryManager    $oauth2ResponseFactoryManager
     */
    public function __construct(TokenStorageInterface $tokenStorage,
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
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        $factory = new DiactorosFactory();
        $request = $factory->createRequest($event->getRequest());

        try {
            $additionalCredentialValues = [];
            $accessTokenId = $this->tokenTypeManager->findToken($request, $additionalCredentialValues);
            if (null === $accessTokenId) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        try {
            $accessToken = $this->accessTokenHandlerManager->find(AccessTokenId::create($accessTokenId));
            if (null === $accessToken || true === $accessToken->isRevoked()) {
                throw new AuthenticationException('Invalid access token.');
            }

            $token = new OAuth2Token($accessToken);
            $result = $this->authenticationManager->authenticate($token);

            $this->tokenStorage->setToken($result);
        } catch (AuthenticationException $e) {
            $psr7Response = $this->oauth2ResponseFactoryManager->getResponse(
                new OAuth2Message(
                    401,
                    OAuth2Message::ERROR_ACCESS_DENIED,
                    'OAuth2 authentication required'
                )
            );
            $factory = new HttpFoundationFactory();
            $event->setResponse($factory->createResponse($psr7Response));
        }
    }
}
