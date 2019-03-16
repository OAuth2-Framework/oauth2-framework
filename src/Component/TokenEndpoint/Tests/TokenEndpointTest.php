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

namespace OAuth2Framework\Component\TokenEndpoint\Tests;

use Http\Message\MessageFactory\GuzzleMessageFactory;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtensionManager;
use OAuth2Framework\Component\TokenEndpoint\TokenEndpoint;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group TokenEndpoint
 */
final class TokenEndpointTest extends TestCase
{
    /**
     * @test
     */
    public function unauthenticatedClient()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('grant_type')
            ->willReturn(new FooGrantType())
            ->shouldBeCalled()
        ;
        $request->getAttribute('client')
            ->willReturn(null)
            ->shouldBeCalled()
        ;

        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getTokenEndpoint()->process($request->reveal(), $handler->reveal());
        } catch (OAuth2Error $e) {
            static::assertEquals(401, $e->getCode());
            static::assertEquals([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theClientIsNotAllowedToUseTheGrantType()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('OWNER_ID'));
        $client->isDeleted()->willReturn(false);
        $client->isGrantTypeAllowed('foo')->willReturn(false);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('grant_type')
            ->willReturn(new FooGrantType())
            ->shouldBeCalled()
        ;
        $request->getAttribute('client')
            ->willReturn($client->reveal())
            ->shouldBeCalled()
        ;

        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getTokenEndpoint()->process($request->reveal(), $handler->reveal());
        } catch (OAuth2Error $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
                'error' => 'unauthorized_client',
                'error_description' => 'The grant type "foo" is unauthorized for this client.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theTokenRequestIsValidAndAnAccessTokenIsIssued()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('OWNER_ID'));
        $client->isDeleted()->willReturn(false);
        $client->isGrantTypeAllowed('foo')->willReturn(true);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('grant_type')
            ->willReturn(new FooGrantType())
            ->shouldBeCalled()
        ;
        $request->getAttribute('client')
            ->willReturn($client->reveal())
            ->shouldBeCalled()
        ;

        $tokenType = $this->prophesize(TokenType::class);
        $tokenType->name()->willReturn('TOKEN_TYPE')->shouldBeCalled();
        $tokenType->getAdditionalInformation()->willReturn(['token_type_foo' => 'token_type_bar'])->shouldBeCalled();
        $request->getAttribute('token_type')
            ->willReturn($tokenType->reveal())
            ->shouldBeCalled()
        ;

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::type(ServerRequestInterface::class))
            ->shouldNotBeCalled();

        $response = $this->getTokenEndpoint()->process($request->reveal(), $handler->reveal());
        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();

        static::assertEquals(200, $response->getStatusCode());
        static::assertRegExp('/^\{"token_type_foo"\:"token_type_bar","token_type"\:"TOKEN_TYPE","access_token"\:"[a-f0-9]{64}","expires_in"\:\d{4}\}$/', $body);
    }

    /**
     * @var TokenEndpoint|null
     */
    private $tokenEndpoint = null;

    private function getTokenEndpoint(): TokenEndpoint
    {
        if (null === $this->tokenEndpoint) {
            $this->tokenEndpoint = new TokenEndpoint(
                $this->getClientRepository(),
                $this->getUserAccountRepository(),
                new TokenEndpointExtensionManager(),
                new GuzzleMessageFactory(),
                $this->getAccessTokenRepository(),
                1800
            );
        }

        return $this->tokenEndpoint;
    }

    /**
     * @var ClientRepository|null
     */
    private $clientRepository = null;

    private function getClientRepository(): ClientRepository
    {
        if (null === $this->clientRepository) {
            $client = $this->prophesize(Client::class);
            $client->isPublic()->willReturn(false);
            $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
            $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
            $client->getOwnerId()->willReturn(new UserAccountId('OWNER_ID'));
            $client->isDeleted()->willReturn(false);
            $client->has('grant_types')->willReturn(true);
            $client->get('grant_types')->willReturn(['foo']);

            $clientRepository = $this->prophesize(ClientRepository::class);
            $clientRepository->find(Argument::type(ClientId::class))->willReturn($client->reveal());

            $this->clientRepository = $clientRepository->reveal();
        }

        return $this->clientRepository;
    }

    /**
     * @var UserAccountRepository|null
     */
    private $userAccountRepository = null;

    private function getUserAccountRepository(): UserAccountRepository
    {
        if (null === $this->userAccountRepository) {
            $userAccountRepository = $this->prophesize(UserAccountRepository::class);

            $this->userAccountRepository = $userAccountRepository->reveal();
        }

        return $this->userAccountRepository;
    }

    /**
     * @var AccessTokenRepository|null
     */
    private $accessTokenRepository = null;

    private function getAccessTokenRepository(): AccessTokenRepository
    {
        if (null === $this->accessTokenRepository) {
            $accessTokenRepository = $this->prophesize(AccessTokenRepository::class);
            $accessTokenRepository->create(Argument::type(ClientId::class), Argument::type(ResourceOwnerId::class), Argument::type(\DateTimeImmutable::class), Argument::type(DataBag::class), Argument::type(DataBag::class), Argument::any())
                ->will(function (array $args) {
                    return new AccessToken(new AccessTokenId(\bin2hex(\random_bytes(32))), $args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
                });
            $accessTokenRepository->save(Argument::type(AccessToken::class))->will(function (array $args) {
            });
            $this->accessTokenRepository = $accessTokenRepository->reveal();
        }

        return $this->accessTokenRepository;
    }
}
