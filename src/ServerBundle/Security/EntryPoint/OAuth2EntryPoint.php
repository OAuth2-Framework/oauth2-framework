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

namespace OAuth2Framework\ServerBundle\Security\EntryPoint;

use OAuth2Framework\Component\Core\Message\OAuth2Message;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class OAuth2EntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * @var OAuth2MessageFactoryManager
     */
    private $oauth2ResponseFactoryManager;

    /**
     * OAuth2EntryPoint constructor.
     *
     * @param OAuth2MessageFactoryManager $oauth2ResponseFactoryManager
     */
    public function __construct(OAuth2MessageFactoryManager $oauth2ResponseFactoryManager)
    {
        $this->oauth2ResponseFactoryManager = $oauth2ResponseFactoryManager;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $psr7Response = $this->oauth2ResponseFactoryManager->getResponse(
            new OAuth2Message(
                401,
                OAuth2Message::ERROR_ACCESS_DENIED,
                'OAuth2 authentication required'
            )
        );
        $factory = new HttpFoundationFactory();

        return $factory->createResponse($psr7Response);
    }
}
