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
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountCheckerManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group UserAccountChecker
 * @group UserAccountCheckerManager
 */
final class UserAccountCheckerManagerTest extends TestCase
{
    /**
     * @test
     */
    public function theUserAccountCheckerManagerCallsAllCheckers()
    {
        $checker1 = $this->prophesize(UserAccountChecker::class);
        $checker1->check(Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalled();

        $checker2 = $this->prophesize(UserAccountChecker::class);
        $checker2->check(Argument::any(), Argument::any(), Argument::any())
            ->shouldBeCalled();

        $authorization = $this->prophesize(Authorization::class);

        $manager = new UserAccountCheckerManager();
        $manager->add($checker1->reveal());
        $manager->add($checker2->reveal());

        $manager->check($authorization->reveal(), null, false);
    }
}
