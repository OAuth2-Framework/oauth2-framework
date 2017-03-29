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

namespace OAuth2Framework\Bundle\Server\Security\EntryPoint;

use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Zend\Diactoros\Response;

class OAuth2EntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * @var AuthenticationEntryPointInterface
     */
    private $entry_point;

    /**
     * OAuth2EntryPoint constructor.
     *
     * @param TokenTypeManager $token_type_manager
     */
    public function __construct(TokenTypeManager $token_type_manager)
    {
        $this->entry_point = new EntryPoint($token_type_manager);
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $factory = new DiactorosFactory();
        $request = $factory->createRequest($request);
        $response = new Response();

        $this->entry_point->start($request, $response);

        $factory = new HttpFoundationFactory();
        $response = $factory->createResponse($response);

        return $response;
    }
}
