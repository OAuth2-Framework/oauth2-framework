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

use Http\Message\MessageFactory;
use OAuth2Framework\ServerBundle\Response\AuthenticateResponseFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class OAuth2EntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var AuthenticateResponseFactory
     */
    private $authenticateResponseFactory;

    /**
     * OAuth2EntryPoint constructor.
     *
     * @param MessageFactory              $messageFactory
     * @param AuthenticateResponseFactory $authenticateResponseFactory
     */
    public function __construct(MessageFactory $messageFactory, AuthenticateResponseFactory $authenticateResponseFactory)
    {
        $this->messageFactory = $messageFactory;
        $this->authenticateResponseFactory = $authenticateResponseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $response = $this->messageFactory->createResponse();
        $factory = new HttpFoundationFactory();

        $oauth2Response = $this->authenticateResponseFactory->createResponse(
            ['error' => 'invalid_grant', 'error_description' => 'Access token is missing.'],
            $response
        );

        return $factory->createResponse($oauth2Response->getResponse());
    }
}
