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

namespace OAuth2Framework\SecurityBundle\Security\Handler;

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final class DefaultFailureHandler implements AuthenticationFailureHandlerInterface
{
    /**
     * @var OAuth2MessageFactoryManager
     */
    private $oauth2ResponseFactoryManager;

    public function __construct(OAuth2MessageFactoryManager $oauth2ResponseFactoryManager)
    {
        $this->oauth2ResponseFactoryManager = $oauth2ResponseFactoryManager;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $psr7Response = $this->oauth2ResponseFactoryManager->getResponse(
            OAuth2Error::accessDenied($exception->getMessage(), [], $exception)
        );
        $factory = new HttpFoundationFactory();

        return $factory->createResponse($psr7Response);
    }
}
