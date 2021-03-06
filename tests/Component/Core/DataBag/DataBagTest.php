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

namespace OAuth2Framework\Tests\Component\Core\DataBag;

use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @group DataBag
 *
 * @internal
 */
final class DataBagTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateADataBag()
    {
        $data = new DataBag([
            'foo' => 'bar',
        ]);
        $data->set('foo', 'BAR');

        static::assertInstanceOf(DataBag::class, $data);
        static::assertTrue($data->has('foo'));
        static::assertFalse($data->has('---'));
    }
}
