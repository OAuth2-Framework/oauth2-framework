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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Tests\UserAccount;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\RedirectToLoginPageException;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\MaxAgeParameterAccountChecker;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use PHPUnit\Framework\TestCase;

/**
 * @group UserAccountChecker
 * @group MaxAgeParameterCheckerAccountChecker
 */
final class MaxAgeParameterAccountCheckerTest extends TestCase
{
    /**
     * @test
     */
    public function theUserAccountIsNotAvailableThenAnExceptionIsThrown()
    {
        $client = $this->prophesize(Client::class);

        $authorization = $this->prophesize(Authorization::class);
        $authorization->hasQueryParam('max_age')->willReturn(true);
        $authorization->getQueryParam('max_age')->willReturn(3600);
        $authorization->getUserAccount()->willReturn(null);
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new MaxAgeParameterAccountChecker();

        try {
            $checker->check($authorization->reveal(), null, false);
            static::fail('The expected exception has not been thrown.');
        } catch (RedirectToLoginPageException $e) {
            static::assertTrue(true);
        }
    }

    /**
     * @test
     */
    public function thereIsNoMaxAgeConstraintThenTheCheckSucceeded()
    {
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')->willReturn(false);

        $userAccount = $this->prophesize(UserAccount::class);

        $authorization = $this->prophesize(Authorization::class);
        $authorization->hasQueryParam('max_age')->willReturn(false);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new MaxAgeParameterAccountChecker();

        $checker->check($authorization->reveal(), $userAccount->reveal(), false);
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

        $authorization = $this->prophesize(Authorization::class);
        $authorization->isUserAccountFullyAuthenticated()->willReturn(false);
        $authorization->hasQueryParam('max_age')->willReturn(false);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new MaxAgeParameterAccountChecker();

        $checker->check($authorization->reveal(), $userAccount->reveal(), false);
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

        $authorization = $this->prophesize(Authorization::class);
        $authorization->hasQueryParam('max_age')->willReturn(true);
        $authorization->getQueryParam('max_age')->willReturn(3600);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $authorization->isUserAccountFullyAuthenticated()->willReturn(false);
        $checker = new MaxAgeParameterAccountChecker();

        $checker->check($authorization->reveal(), $userAccount->reveal(), false);
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

        $authorization = $this->prophesize(Authorization::class);
        $authorization->isUserAccountFullyAuthenticated()->willReturn(false);
        $authorization->hasQueryParam('max_age')->willReturn(true);
        $authorization->getQueryParam('max_age')->willReturn(3600);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new MaxAgeParameterAccountChecker();

        try {
            $checker->check($authorization->reveal(), $userAccount->reveal(), false);
            static::fail('The expected exception has not been thrown.');
        } catch (RedirectToLoginPageException $e) {
            static::assertTrue(true);
        }
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

        $authorization = $this->prophesize(Authorization::class);
        $authorization->isUserAccountFullyAuthenticated()->willReturn(false);
        $authorization->hasQueryParam('max_age')->willReturn(true);
        $authorization->getQueryParam('max_age')->willReturn(3600);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new MaxAgeParameterAccountChecker();

        try {
            $checker->check($authorization->reveal(), $userAccount->reveal(), false);
            static::fail('The expected exception has not been thrown.');
        } catch (RedirectToLoginPageException $e) {
            static::assertTrue(true);
        }
    }
}
