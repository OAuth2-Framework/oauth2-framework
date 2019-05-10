<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\Tests\User;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\User\MaxAgeParameterAuthenticationChecker;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use PHPUnit\Framework\TestCase;

/**
 * @group UserChecker
 * @group MaxAgeParameterCheckerAccountChecker
 */
final class MaxAgeParameterCheckerTest extends TestCase
{
    /**
     * @test
     */
    public function theUserHasNeverBeenConnected()
    {
        $client = $this->prophesize(Client::class);

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')->willReturn(true);
        $authorization->getQueryParam('max_age')->willReturn(3600);
        $authorization->hasUserAccount()->willReturn(false);
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new MaxAgeParameterAuthenticationChecker();

        static::assertTrue($checker->isAuthenticationNeeded($authorization->reveal()));
    }

    /**
     * @test
     */
    public function thereIsNoMaxAgeConstraintThenTheCheckSucceeded()
    {
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')->willReturn(false);

        $userAccount = $this->prophesize(UserAccount::class);

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')->willReturn(false);
        $authorization->hasUserAccount()->willReturn(true);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new MaxAgeParameterAuthenticationChecker();

        $checker->isAuthenticationNeeded($authorization->reveal(), $userAccount->reveal(), false);
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function thereIsConstraintFromTheClientThatIsSatisfied()
    {
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')->willReturn(true);
        $client->get('default_max_age')->willReturn(3600);

        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getLastLoginAt()->willReturn(\time() - 100);

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')->willReturn(false);
        $authorization->hasUserAccount()->willReturn(true);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new MaxAgeParameterAuthenticationChecker();

        $checker->isAuthenticationNeeded($authorization->reveal(), $userAccount->reveal(), false);
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function thereIsConstraintFromTheAuthorizationThatIsSatisfied()
    {
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')->willReturn(false);

        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getLastLoginAt()->willReturn(\time() - 100);

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')->willReturn(true);
        $authorization->getQueryParam('max_age')->willReturn(3600);
        $authorization->hasUserAccount()->willReturn(true);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new MaxAgeParameterAuthenticationChecker();

        $checker->isAuthenticationNeeded($authorization->reveal(), $userAccount->reveal(), false);
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function thereIsAConstraintButTheUserNeverLoggedIn()
    {
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')->willReturn(false);

        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getLastLoginAt()->willReturn(null);

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')->willReturn(true);
        $authorization->getQueryParam('max_age')->willReturn(3600);
        $authorization->hasUserAccount()->willReturn(true);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new MaxAgeParameterAuthenticationChecker();

        static::assertTrue($checker->isAuthenticationNeeded($authorization->reveal()));
    }

    /**
     * @test
     */
    public function thereIsAConstraintThatIsNotSatisfied()
    {
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')->willReturn(false);

        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getLastLoginAt()->willReturn(\time() - 10000);

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')->willReturn(true);
        $authorization->getQueryParam('max_age')->willReturn(3600);
        $authorization->hasUserAccount()->willReturn(true);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new MaxAgeParameterAuthenticationChecker();

        static::assertTrue($checker->isAuthenticationNeeded($authorization->reveal()));
    }
}
