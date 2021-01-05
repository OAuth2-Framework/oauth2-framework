<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\Tests\User;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationCheckerManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @group UserChecker
 * @group UserCheckerManager
 *
 * @internal
 */
final class UserCheckerManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function theUserCheckerManagerCallsAllCheckers()
    {
        $checker1 = $this->prophesize(UserAuthenticationChecker::class);
        $checker1->isAuthenticationNeeded(Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalled()
        ;

        $checker2 = $this->prophesize(UserAuthenticationChecker::class);
        $checker2->isAuthenticationNeeded(Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalled()
        ;

        $authorization = $this->prophesize(AuthorizationRequest::class);

        $manager = new UserAuthenticationCheckerManager();
        $manager->add($checker1->reveal());
        $manager->add($checker2->reveal());

        $manager->isAuthenticationNeeded($authorization->reveal());
    }
}
