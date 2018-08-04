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

namespace OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\Tests;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Message;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountManager;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialsGrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @group GrantType
 * @group ResourceOwnerPasswordCredential
 */
final class ResourceOwnerPasswordCredentialsGrantTypeTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        static::assertEquals([], $this->getGrantType()->associatedResponseTypes());
        static::assertEquals('password', $this->getGrantType()->name());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters()
    {
        $request = $this->buildRequest(['password' => 'PASSWORD']);

        try {
            $this->getGrantType()->checkRequest($request->reveal());
            static::fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Message $e) {
            static::assertEquals(400, $e->getCode());
            static::assertEquals([
                'error' => 'invalid_request',
                'error_description' => 'Missing grant type parameter(s): username.',
            ], $e->getData());
        }
    }

    /**
     * @test
     */
    public function theRequestHaveAllRequiredParameters()
    {
        $request = $this->buildRequest(['password' => 'PASSWORD', 'username' => 'USERNAME']);

        $this->getGrantType()->checkRequest($request->reveal());
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPrepared()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            new UserAccountId('USER_ACCOUNT_ID')
        );
        $request = $this->buildRequest(['password' => 'PASSWORD', 'username' => 'USERNAME']);
        $grantTypeData = new GrantTypeData($client);

        $receivedGrantTypeData = $this->getGrantType()->prepareResponse($request->reveal(), $grantTypeData);
        static::assertSame($receivedGrantTypeData, $grantTypeData);
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClient()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            new UserAccountId('USER_ACCOUNT_ID')
        );
        $request = $this->buildRequest(['password' => 'PASSWORD', 'username' => 'USERNAME']);
        $grantTypeData = new GrantTypeData($client);

        $receivedGrantTypeData = $this->getGrantType()->grant($request->reveal(), $grantTypeData);
        static::assertEquals('USERNAME', $receivedGrantTypeData->getResourceOwnerId()->getValue());
        static::assertEquals('CLIENT_ID', $receivedGrantTypeData->getClient()->getPublicId()->getValue());
    }

    /**
     * @var ResourceOwnerPasswordCredentialsGrantType|null
     */
    private $grantType = null;

    private function getGrantType(): ResourceOwnerPasswordCredentialsGrantType
    {
        if (null === $this->grantType) {
            $userAccount = $this->prophesize(UserAccount::class);
            $userAccount->getPublicId()->willReturn(new UserAccountId('USERNAME'));
            $userAccount->getUserAccountId()->willReturn(new UserAccountId('USERNAME'));

            $userAccountManager = $this->prophesize(UserAccountManager::class);
            $userAccountManager->isPasswordCredentialValid($userAccount->reveal(), 'PASSWORD')->willReturn(true);

            $userAccountRepository = $this->prophesize(UserAccountRepository::class);
            $userAccountRepository->findOneByUsername(new UserAccountId('USERNAME'))->willReturn($userAccount->reveal());

            $this->grantType = new ResourceOwnerPasswordCredentialsGrantType(
                $userAccountManager->reveal(),
                $userAccountRepository->reveal()
            );
        }

        return $this->grantType;
    }

    private function buildRequest(array $data): ObjectProphecy
    {
        $body = $this->prophesize(StreamInterface::class);
        $body->getContents()->willReturn(\http_build_query($data));
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->hasHeader('Content-Type')->willReturn(true);
        $request->getHeader('Content-Type')->willReturn(['application/x-www-form-urlencoded']);
        $request->getBody()->willReturn($body->reveal());
        $request->getParsedBody()->willReturn([]);

        return $request;
    }
}
