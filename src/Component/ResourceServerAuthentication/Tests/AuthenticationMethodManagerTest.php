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

namespace OAuth2Framework\Component\ResourceServerAuthentication\Tests;

use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMethod;
use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMethodManager;
use PHPUnit\Framework\TestCase;

/**
 * @group TokenEndpoint
 * @group ResourceServerAuthentication
 */
class AuthenticationMethodManagerTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $method = $this->prophesize(AuthenticationMethod::class);
        $method->getSupportedMethods()->willReturn(['foo']);
        $method->getSchemesParameters()->willReturn(['Basic realm="Realm",charset="UTF-8"']);
        $manager = new AuthenticationMethodManager();
        $manager
            ->add($method->reveal())
        ;
        self::assertTrue($manager->has('foo'));
        self::assertEquals(['foo'], $manager->list());
        self::assertInstanceOf(AuthenticationMethod::class, $manager->get('foo'));
        self::assertEquals(1, count($manager->all()));
        self::assertEquals(['Basic realm="Realm",charset="UTF-8"'], $manager->getSchemesParameters());
    }
}
