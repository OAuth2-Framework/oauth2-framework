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

namespace OAuth2Framework\Component\Server\ResourceOwnerPasswordCredentialsGrantType\Tests;

use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountManager;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\Server\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialsGrantType;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group GrantType
 * @group ResourceOwnerPasswordCredentials
 */
final class ResourceOwnerPasswordCredentialsGrantTypeTest extends TestCase
{
    /**
     * @test
     */
    public function genericInformation()
    {
        self::assertEquals([], $this->getGrantType()->getAssociatedResponseTypes());
        self::assertEquals('password', $this->getGrantType()->getGrantType());
    }

    /**
     * @test
     */
    public function theRequestHaveMissingParameters()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['password' => 'PASSWORD']);

        try {
            $this->getGrantType()->checkTokenRequest($request->reveal());
            $this->fail('An OAuth2 exception should be thrown.');
        } catch (OAuth2Exception $e) {
            self::assertEquals(400, $e->getCode());
            self::assertEquals([
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
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['password' => 'PASSWORD', 'username' => 'USERNAME']);

        $this->getGrantType()->checkTokenRequest($request->reveal());
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function theTokenResponseIsCorrectlyPrepared()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['password' => 'PASSWORD', 'username' => 'USERNAME']);
        $grantTypeData = GrantTypeData::create($client);

        $receivedGrantTypeData = $this->getGrantType()->prepareTokenResponse($request->reveal(), $grantTypeData);
        self::assertSame($receivedGrantTypeData, $grantTypeData);
    }

    /**
     * @test
     */
    public function theGrantTypeCanGrantTheClient()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client->eraseMessages();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['password' => 'PASSWORD', 'username' => 'USERNAME']);
        $grantTypeData = GrantTypeData::create($client);

        $receivedGrantTypeData = $this->getGrantType()->grant($request->reveal(), $grantTypeData);
        self::assertNotSame($receivedGrantTypeData, $grantTypeData);
        self::assertEquals('USERNAME', $receivedGrantTypeData->getResourceOwnerId()->getValue());
        self::assertEquals('CLIENT_ID', $receivedGrantTypeData->getClient()->getPublicId()->getValue());
    }

    /**
     * @var ResourceOwnerPasswordCredentialsGrantType|null
     */
    private $grantType = null;

    private function getGrantType(): ResourceOwnerPasswordCredentialsGrantType
    {
        if (null === $this->grantType) {
            $userAccount = $this->prophesize(UserAccount::class);
            $userAccount->getPublicId()->willReturn(UserAccountId::create('USERNAME'));

            $userAccountManager = $this->prophesize(UserAccountManager::class);
            $userAccountManager->isPasswordCredentialValid($userAccount->reveal(), 'PASSWORD')->willReturn(true);

            $userAccountRepository = $this->prophesize(UserAccountRepository::class);
            $userAccountRepository->findOneByUsername(UserAccountId::create('USERNAME'))->willReturn($userAccount->reveal());

            $this->grantType = new ResourceOwnerPasswordCredentialsGrantType(
                $userAccountManager->reveal(),
                $userAccountRepository->reveal()
            );
        }

        return $this->grantType;
    }
}