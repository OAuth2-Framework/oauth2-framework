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
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\PromptLoginParameterAccountChecker;
use PHPUnit\Framework\TestCase;

/**
 * @group UserAccountChecker
 * @group PromptLoginParameterCheckerAccountChecker
 */
final class PromptLoginParameterAccountCheckerTest extends TestCase
{
    /**
     * @test
     */
    public function whenTheLoginParameterIsSetAndTheUserNotFullyAuthenticatedARedirectionToTheLoginPageIsThrown()
    {
        $authorization = $this->prophesize(Authorization::class);
        $authorization->hasPrompt('login')->willReturn(true);
        $authorization->isUserAccountFullyAuthenticated()->willReturn(false);
        $checker = new PromptLoginParameterAccountChecker();

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
    public function whenTheLoginParameterIsSetAndTheUserFullyAuthenticatedThenCheckSucceeded()
    {
        $authorization = $this->prophesize(Authorization::class);
        $authorization->hasPrompt('login')->willReturn(true);
        $authorization->isUserAccountFullyAuthenticated()->willReturn(true);
        $checker = new PromptLoginParameterAccountChecker();

        $checker->check($authorization->reveal(), null, true);
        static::assertTrue(true);
    }
}
