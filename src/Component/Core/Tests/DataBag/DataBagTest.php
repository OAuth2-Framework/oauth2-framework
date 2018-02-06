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
class DataBagTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateADataBag()
    {
        $data = DataBag::create([
            'foo' => 'bar',
        ]);
        $dataModified = $data->with('foo', 'BAR');
        $dataModified = $dataModified->without('bar');
        $dataModified = $dataModified->without('foo');

        self::assertInstanceOf(DataBag::class, $data);
        self::assertInstanceOf(DataBag::class, $dataModified);
        self::assertNotSame($dataModified, $data);
        self::assertTrue($data->has('foo'));
        self::assertFalse($data->has('---'));
        self::assertEquals('bar', $data->get('foo'));
        self::assertEquals('{"foo":"bar"}', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        self::assertEquals('[]', json_encode($dataModified, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
