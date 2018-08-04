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

namespace OAuth2Framework\Component\Core\Tests\DataBag;

use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @group DataBag
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
        $data->with('foo', 'BAR');
        $data = $data->without('bar');
        $data = $data->without('foo');

        static::assertInstanceOf(DataBag::class, $data);
        static::assertFalse($data->has('foo'));
        static::assertFalse($data->has('---'));
        static::assertEquals('[]', \json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
