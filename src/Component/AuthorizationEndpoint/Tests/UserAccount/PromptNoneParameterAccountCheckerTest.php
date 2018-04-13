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
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\CreateRedirectionException;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\PromptNoneParameterAccountChecker;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use PHPUnit\Framework\TestCase;

/**
 * @group UserAccountChecker
 * @group PromptNoneParameterAccountChecker
 */
class PromptNoneParameterAccountCheckerTest extends TestCase
{
    /**
     * @test
     */
    public function theUserAccountIsNotAvailableAndThePromptNoneIsSetThenAnExceptionIsThrown()
    {
        $client = $this->prophesize(Client::class);

        $authorization = $this->prophesize(Authorization::class);
        $authorization->hasPrompt('none')->willReturn(true);
        $authorization->getUserAccount()->willReturn(null);
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new PromptNoneParameterAccountChecker();

        try {
            $checker->check($authorization->reveal());
            $this->fail('The expected exception has not been thrown.');
        } catch (CreateRedirectionException $e) {
            self::assertTrue(true);
        }
    }

    /**
     * @test
     */
    public function theUserAccountIsAvailableAndThePromptNoneIsSetThenTheCheckSucceeded()
    {
        $userAccount = $this->prophesize(UserAccount::class);

        $client = $this->prophesize(Client::class);

        $authorization = $this->prophesize(Authorization::class);
        $authorization->hasPrompt('none')->willReturn(true);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new PromptNoneParameterAccountChecker();

        $checker->check($authorization->reveal());
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function theUserAccountIsAvailableAndThePromptNoneIsNotSetThenTheCheckSucceeded()
    {
        $userAccount = $this->prophesize(UserAccount::class);

        $client = $this->prophesize(Client::class);

        $authorization = $this->prophesize(Authorization::class);
        $authorization->hasPrompt('none')->willReturn(false);
        $authorization->getUserAccount()->willReturn($userAccount->reveal());
        $authorization->getClient()->willReturn($client->reveal());
        $checker = new PromptNoneParameterAccountChecker();

        $checker->check($authorization->reveal());
        self::assertTrue(true);
    }
}
