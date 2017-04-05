<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Security\Firewall;

use OAuth2Framework\Bundle\Server\Security\Authentication\Token\OAuth2Token;
use OAuth2Framework\Component\Server\Security\AccessTokenHandlerInterface;
use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class OAuth2Listener implements ListenerInterface
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
     * @var ListenerInterface
     */
    private $listener;

    /**
     * OAuth2Listener constructor.
     *
     * @param TokenStorageInterface          $tokenStorage
     * @param AuthenticationManagerInterface $authenticationManager
     * @param TokenTypeManager               $token_type_manager
     * @param AccessTokenHandlerInterface    $accessToken_handler
     */
    public function __construct(TokenStorageInterface $tokenStorage,
                                AuthenticationManagerInterface $authenticationManager,
                                TokenTypeManager $token_type_manager,
                                AccessTokenHandlerInterface $accessToken_handler
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->listener = new Listener(
            $token_type_manager,
            $accessToken_handler
        );
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        $factory = new DiactorosFactory();
        $request = $factory->createRequest($event->getRequest());

        try {
            $accessToken = $this->listener->handle($request);
        } catch (BaseExceptionInterface $e) {
            return;
        }

        try {
            $token = new OAuth2Token();
            $token->setToken($accessToken->getToken());
            $token->setAccessToken($accessToken);

            $result = $this->authenticationManager->authenticate($token);

            $this->tokenStorage->setToken($result);
        } catch (AuthenticationException $e) {
            if (null !== $e->getPrevious()) {
                $e = $e->getPrevious();
            }
            $response = new Response($e->getMessage(), 401);
            $event->setResponse($response);
        }
    }
}
