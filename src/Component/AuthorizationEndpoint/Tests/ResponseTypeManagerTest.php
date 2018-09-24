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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Tests;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use PHPUnit\Framework\TestCase;

/**
 * @group AuthorizationEndpoint
 * @group ResponseTypeManager
 */
final class ResponseTypeManagerTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The response type "bar" is not supported.
     */
    public function basicCalls()
    {
        $manager = new ResponseTypeManager();

        $type = $this->prophesize(ResponseType::class);
        $type->name()->willReturn('foo');

        $manager->add($type->reveal());

        static::assertTrue($manager->has('foo'));
        static::assertFalse($manager->has('bar'));
        static::assertInstanceOf(ResponseType::class, $manager->get('foo'));
        static::assertEquals(['foo'], $manager->list());
        static::assertEquals(1, \count($manager->all()));

        $manager->get('bar');
    }
}
