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

namespace OAuth2Framework\Component\ResourceServerAuthentication\Tests;

use Psr\Http\Server\RequestHandlerInterface;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMethod;
use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpoint
 * @group ResourceServerAuthenticationMiddleware
 */
final class AuthenticationMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function noResourceServerIsFoundInTheRequest()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')->willReturn([])->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $ResourceServerRepository = $this->prophesize(ResourceServerRepository::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldBeCalled()
            ->willReturn($response->reveal())
        ;

        $this->getResourceServerAuthenticationMiddleware($ResourceServerRepository->reveal())->process($request->reveal(), $handler->reveal());
    }

    /**
     * @test
     */
    public function aResourceServerIdIsSetButTheResourceServerDoesNotExist()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn([
                'Basic '.base64_encode('FOO:BAR'),
            ])
            ->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $ResourceServerRepository = $this->prophesize(ResourceServerRepository::class);
        $ResourceServerRepository->find(Argument::type(ResourceServerId::class))->willReturn(null)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled()
        ;

        try {
            $this->getResourceServerAuthenticationMiddleware($ResourceServerRepository->reveal())->process($request->reveal(), $handler->reveal());
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(401, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_ResourceServer',
                'error_description' => 'Unknown ResourceServer or ResourceServer not authenticated.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function aResourceServerIdIsSetButTheResourceServerIsDeleted()
    {
        $ResourceServer = ResourceServer::createEmpty();
        $ResourceServer = $ResourceServer->create(
            ResourceServerId::create('FOO'),
            DataBag::create([]),
            null
        );
        $ResourceServer = $ResourceServer->markAsDeleted();
        $ResourceServer->eraseMessages();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn([
                'Basic '.base64_encode('FOO:BAR'),
            ])
            ->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $ResourceServerRepository = $this->prophesize(ResourceServerRepository::class);
        $ResourceServerRepository->find(Argument::type(ResourceServerId::class))->willReturn($ResourceServer)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled()
        ;

        try {
            $this->getResourceServerAuthenticationMiddleware($ResourceServerRepository->reveal())->process($request->reveal(), $handler->reveal());
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(401, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_ResourceServer',
                'error_description' => 'Unknown ResourceServer or ResourceServer not authenticated.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function aResourceServerIdIsSetButTheResourceServerCredentialsExpired()
    {
        $ResourceServer = ResourceServer::createEmpty();
        $ResourceServer = $ResourceServer->create(
            ResourceServerId::create('FOO'),
            DataBag::create([
                'ResourceServer_authentication' => 'ResourceServer_secret_basic',
                'ResourceServer_secret' => 'BAR',
                'ResourceServer_secret_expires_at' => time() - 1,
            ]),
            null
        );
        $ResourceServer->eraseMessages();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn([
                'Basic '.base64_encode('FOO:BAR'),
            ])
            ->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $ResourceServerRepository = $this->prophesize(ResourceServerRepository::class);
        $ResourceServerRepository->find(Argument::type(ResourceServerId::class))->willReturn($ResourceServer)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled()
        ;

        try {
            $this->getResourceServerAuthenticationMiddleware($ResourceServerRepository->reveal())->process($request->reveal(), $handler->reveal());
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(401, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_ResourceServer',
                'error_description' => 'ResourceServer credentials expired.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function aResourceServerIdIsSetButTheAuthenticationMethodIsNotSupportedByTheResourceServer()
    {
        $ResourceServer = ResourceServer::createEmpty();
        $ResourceServer = $ResourceServer->create(
            ResourceServerId::create('FOO'),
            DataBag::create([
                'ResourceServer_authentication' => 'none',
            ]),
            null
        );
        $ResourceServer->eraseMessages();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn([
                'Basic '.base64_encode('FOO:BAR'),
            ])
            ->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $ResourceServerRepository = $this->prophesize(ResourceServerRepository::class);
        $ResourceServerRepository->find(Argument::type(ResourceServerId::class))->willReturn($ResourceServer)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled()
        ;

        try {
            $this->getResourceServerAuthenticationMiddleware($ResourceServerRepository->reveal())->process($request->reveal(), $handler->reveal());
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(401, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_ResourceServer',
                'error_description' => 'Unknown ResourceServer or ResourceServer not authenticated.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function aResourceServerIdIsSetButTheResourceServerIsNotAuthenticated()
    {
        $ResourceServer = ResourceServer::createEmpty();
        $ResourceServer = $ResourceServer->create(
            ResourceServerId::create('FOO'),
            DataBag::create([
                'ResourceServer_authentication' => 'ResourceServer_secret_basic',
                'ResourceServer_secret' => 'BAR',
            ]),
            null
        );
        $ResourceServer->eraseMessages();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn([
                'Basic '.base64_encode('FOO:BAD_SECRET'),
            ])
            ->shouldBeCalled();
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $ResourceServerRepository = $this->prophesize(ResourceServerRepository::class);
        $ResourceServerRepository->find(Argument::type(ResourceServerId::class))->willReturn($ResourceServer)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled()
        ;

        try {
            $this->getResourceServerAuthenticationMiddleware($ResourceServerRepository->reveal())->process($request->reveal(), $handler->reveal());
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(401, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_ResourceServer',
                'error_description' => 'Unknown ResourceServer or ResourceServer not authenticated.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function aResourceServerIsFullyAuthenticated()
    {
        $ResourceServer = ResourceServer::createEmpty();
        $ResourceServer = $ResourceServer->create(
            ResourceServerId::create('FOO'),
            DataBag::create([
                'ResourceServer_authentication' => 'ResourceServer_secret_basic',
                'ResourceServer_secret' => 'BAR',
            ]),
            null
        );
        $ResourceServer->eraseMessages();

        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('Authorization')
            ->willReturn([
                'Basic '.base64_encode('FOO:BAR'),
            ])
            ->shouldBeCalled();
        $request->withAttribute('ResourceServer', $ResourceServer)->shouldBeCalled()->willReturn($request->reveal());
        $request->withAttribute('ResourceServer_authentication_method', Argument::type(AuthenticationMethod::class))->shouldBeCalled()->willReturn($request->reveal());
        $request->withAttribute('ResourceServer_credentials', 'BAR')->shouldBeCalled()->willReturn($request->reveal());
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $ResourceServerRepository = $this->prophesize(ResourceServerRepository::class);
        $ResourceServerRepository->find(Argument::type(ResourceServerId::class))->willReturn($ResourceServer)->shouldBeCalled();
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldBeCalled()
            ->willReturn($response->reveal())
        ;

        $this->getResourceServerAuthenticationMiddleware($ResourceServerRepository->reveal())->process($request->reveal(), $handler->reveal());
    }

    /**
     * @param ResourceServerRepository $ResourceServerRepository
     *
     * @return AuthenticationMiddleware
     */
    private function getResourceServerAuthenticationMiddleware(ResourceServerRepository $ResourceServerRepository): AuthenticationMiddleware
    {
        $authenticationMethodManager = new AuthenticationMethodManager();
        $authenticationMethodManager->add(new ResourceServerSecretBasic('Real'));

        $ResourceServerAuthenticationMiddleware = new AuthenticationMiddleware(
            $ResourceServerRepository,
            $authenticationMethodManager
        );

        return $ResourceServerAuthenticationMiddleware;
    }
}
