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
use OAuth2Framework\Component\Core\AccessToken\AccessTokenIdGenerator;
use Psr\Http\Server\RequestHandlerInterface;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtensionManager;
use OAuth2Framework\Component\TokenEndpoint\TokenEndpoint;
use OAuth2Framework\Component\TokenType\TokenType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpoint
 */
class TokenEndpointTest extends TestCase
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
        } catch (OAuth2Exception $e) {
            self::assertEquals(401, $e->getCode());
            self::assertEquals([
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
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('OWNER_ID')
        );
        $client->eraseMessages();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('grant_type')
            ->willReturn(new FooGrantType())
            ->shouldBeCalled()
        ;
        $request->getAttribute('client')
            ->willReturn($client)
            ->shouldBeCalled()
        ;

        $handler = $this->prophesize(RequestHandlerInterface::class);

        try {
            $this->getTokenEndpoint()->process($request->reveal(), $handler->reveal());
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
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
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'grant_types' => ['foo'],
            ]),
            UserAccountId::create('OWNER_ID')
        );
        $client->eraseMessages();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('grant_type')
            ->willReturn(new FooGrantType())
            ->shouldBeCalled()
        ;
        $request->getAttribute('client')
            ->willReturn($client)
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

        self::assertEquals(200, $response->getStatusCode());
        self::assertRegexp('/^\{"token_type_foo"\:"token_type_bar","token_type"\:"TOKEN_TYPE","access_token"\:"ACCESS_TOKEN_ID","expires_in"\:\d{4}\}$/', $body);
    }

    /**
     * @var null|TokenEndpoint
     */
    private $tokenEndpoint = null;

    /**
     * @return TokenEndpoint
     */
    private function getTokenEndpoint(): TokenEndpoint
    {
        if (null === $this->tokenEndpoint) {
            $this->tokenEndpoint = new TokenEndpoint(
                $this->getClientRepository(),
                $this->getUserAccountRepository(),
                new TokenEndpointExtensionManager(),
                new GuzzleMessageFactory(),
                $this->getAccessTokenRepository(),
                $this->getAccessTokenIdGenerator(),
                1800
            );
        }

        return $this->tokenEndpoint;
    }

    /**
     * @var null|ClientRepository
     */
    private $clientRepository = null;

    /**
     * @return ClientRepository
     */
    private function getClientRepository(): ClientRepository
    {
        if (null === $this->clientRepository) {
            $client = Client::createEmpty();
            $client = $client->create(
                ClientId::create('CLIENT_ID'),
                DataBag::create([
                    'grant_types' => ['foo'],
                ]),
                UserAccountId::create('OWNER_ID')
            );
            $client->eraseMessages();

            $clientRepository = $this->prophesize(ClientRepository::class);
            $clientRepository->find(Argument::type(ClientId::class))->willReturn($client);

            $this->clientRepository = $clientRepository->reveal();
        }

        return $this->clientRepository;
    }

    /**
     * @var null|UserAccountRepository
     */
    private $userAccountRepository = null;

    /**
     * @return UserAccountRepository
     */
    private function getUserAccountRepository(): UserAccountRepository
    {
        if (null === $this->userAccountRepository) {
            $userAccountRepository = $this->prophesize(UserAccountRepository::class);

            $this->userAccountRepository = $userAccountRepository->reveal();
        }

        return $this->userAccountRepository;
    }

    /**
     * @var null|AccessTokenRepository
     */
    private $accessTokenRepository = null;

    /**
     * @return AccessTokenRepository
     */
    private function getAccessTokenRepository(): AccessTokenRepository
    {
        if (null === $this->accessTokenRepository) {
            $accessTokenRepository = $this->prophesize(AccessTokenRepository::class);
            $accessTokenRepository->save(Argument::type(AccessToken::class))->willReturn(null);

            $this->accessTokenRepository = $accessTokenRepository->reveal();
        }

        return $this->accessTokenRepository;
    }

    /**
     * @var null|AccessTokenIdGenerator
     */
    private $accessTokenIdGenerator = null;

    /**
     * @return AccessTokenIdGenerator
     */
    private function getAccessTokenIdGenerator(): AccessTokenIdGenerator
    {
        if (null === $this->accessTokenIdGenerator) {
            $accessTokenIdGenerator = $this->prophesize(AccessTokenIdGenerator::class);
            $accessTokenIdGenerator
                ->create(Argument::type(ResourceOwnerId::class), Argument::type(ClientId::class), Argument::type(DataBag::class), Argument::type(DataBag::class), null)
                ->will(function () {
                    return AccessTokenId::create('ACCESS_TOKEN_ID');
                });
            $this->accessTokenIdGenerator = $accessTokenIdGenerator->reveal();
        }

        return $this->accessTokenIdGenerator;
    }
}
