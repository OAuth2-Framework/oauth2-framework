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

namespace OAuth2Framework\Component\Server\TokenEndpoint\Tests;

use Interop\Http\Server\RequestHandlerInterface;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\TokenEndpoint\AuthenticationMethod\AuthenticationMethodManager;
use OAuth2Framework\Component\Server\TokenEndpoint\ClientAuthenticationMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpoint
 * @group ClientAuthenticationMiddleware
 */
final class ClientAuthenticationMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function noClientIsFoundInTheRequest()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $clientRepository = $this->prophesize(ClientRepository::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldBeCalled()
            ->willReturn($response->reveal())
        ;

        $this->getClientAuthenticationMiddleware($clientRepository->reveal())->process($request->reveal(), $handler->reveal());
    }

    /**
     * @param ClientRepository $clientRepository
     *
     * @return ClientAuthenticationMiddleware
     */
    private function getClientAuthenticationMiddleware(ClientRepository $clientRepository): ClientAuthenticationMiddleware
    {
        $authenticationMethodManager = new AuthenticationMethodManager();

        $clientAuthenticationMiddleware = new ClientAuthenticationMiddleware(
            $clientRepository,
            $authenticationMethodManager
        );

        return $clientAuthenticationMiddleware;
    }
}
