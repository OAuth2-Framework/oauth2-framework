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

namespace OAuth2Framework\Component\JwtBearerGrant\Tests;

use OAuth2Framework\Component\JwtBearerGrant\TrustedIssuer;
use OAuth2Framework\Component\JwtBearerGrant\TrustedIssuerManager;
use PHPUnit\Framework\TestCase;

/**
 * @group GrantType
 * @group JwtBearer
 */
class TrustedIssuerTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        $manager = new TrustedIssuerManager();
        $issuer = $this->prophesize(TrustedIssuer::class);
        $issuer->name()->willReturn('foo');

        $manager->add($issuer->reveal());
        self::assertEquals(['foo'], $manager->list());
        self::assertTrue($manager->has('foo'));
        self::assertInstanceOf(TrustedIssuer::class, $manager->get('foo'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The issuer with name "foo" is not known.
     */
    public function theIssuerIsNotSupported()
    {
        $manager = new TrustedIssuerManager();
        $manager->get('foo');
    }
}
