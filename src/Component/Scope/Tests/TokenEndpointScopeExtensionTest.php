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

namespace OAuth2Framework\Component\Scope\Tests;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Scope\Policy\NoScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Scope\Scope;
use OAuth2Framework\Component\Scope\ScopeRepository;
use OAuth2Framework\Component\Scope\TokenEndpointScopeExtension;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group TokenEndpointScopeExtension
 */
class TokenEndpointScopeExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function theRequestHasNoScope()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([]);
        $grantTypeData = GrantTypeData::create($client);
        $grantType = $this->prophesize(GrantType::class);
        $next = function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType): GrantTypeData {
            return $grantTypeData;
        };

        $result = $this->getExtension()->beforeAccessTokenIssuance($request->reveal(), $grantTypeData, $grantType->reveal(), $next);
        self::assertSame($grantTypeData, $result);
    }

    /**
     * @test
     */
    public function theRequestedScopeIsNotSupported()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
            'scope' => 'café',
        ]);
        $grantTypeData = GrantTypeData::create($client);
        $grantType = $this->prophesize(GrantType::class);
        $next = function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType): GrantTypeData {
            return $grantTypeData;
        };

        try {
            $this->getExtension()->beforeAccessTokenIssuance($request->reveal(), $grantTypeData, $grantType->reveal(), $next);
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
                'error' => 'invalid_scope',
                'error_description' => 'An unsupported scope was requested. Available scope is/are: scope1 ,scope2.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRequestedScopeIsValid()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn([
            'scope' => 'scope2 scope1',
        ]);
        $grantTypeData = GrantTypeData::create($client);
        $grantType = $this->prophesize(GrantType::class);
        $next = function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType): GrantTypeData {
            return $grantTypeData;
        };

        $result = $this->getExtension()->beforeAccessTokenIssuance($request->reveal(), $grantTypeData, $grantType->reveal(), $next);
        self::assertNotSame($grantTypeData, $result);
        self::assertTrue($result->hasParameter('scope'));
        self::assertEquals('scope2 scope1', $result->getParameter('scope'));
    }

    /**
     * @test
     */
    public function after()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            AccessTokenId::create('ACCESS_TOKEN_ID'),
            $client->getPublicId(),
            $client->getPublicId(),
            DataBag::create([]),
            DataBag::create([]),
            new \DateTimeImmutable('now +1 hour'),
            null
        );
        $accessToken->eraseMessages();

        $next = function (Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken): array {
            return $accessToken->getResponseData();
        };

        $result = $this->getExtension()->afterAccessTokenIssuance($client, $client, $accessToken, $next);
        self::assertEquals(2, count($result));
    }

    /**
     * @var null|TokenEndpointScopeExtension
     */
    private $extension = null;

    /**
     * @return TokenEndpointScopeExtension
     */
    private function getExtension(): TokenEndpointScopeExtension
    {
        if (null === $this->extension) {
            $scope1 = $this->prophesize(Scope::class);
            $scope1->name()->willReturn('scope1');
            $scope1->__toString()->willReturn('scope1');
            $scope2 = $this->prophesize(Scope::class);
            $scope2->name()->willReturn('scope2');
            $scope2->__toString()->willReturn('scope2');
            $scopeRepository = $this->prophesize(ScopeRepository::class);
            $scopeRepository->all()->willReturn([
                $scope1->reveal(),
                $scope2->reveal(),
            ]);

            $scopePolicyManager = new ScopePolicyManager();
            $scopePolicyManager->add(new NoScopePolicy(), true);

            $this->extension = new TokenEndpointScopeExtension(
                $scopeRepository->reveal(),
                $scopePolicyManager
            );
        }

        return $this->extension;
    }
}
