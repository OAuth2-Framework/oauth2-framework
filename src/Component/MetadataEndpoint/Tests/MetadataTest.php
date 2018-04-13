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

namespace OAuth2Framework\Component\MetadataEndpoint\Tests;

use OAuth2Framework\Component\MetadataEndpoint\Metadata;
use PHPUnit\Framework\TestCase;

/**
 * @group Compiler
 */
class MetadataTest extends TestCase
{
    /**
     * @test
     */
    public function genericObjectMethods()
    {
        $metadata = new Metadata();
        self::assertFalse($metadata->has('foo'));
        $metadata->set('foo', 'bar');
        self::assertTrue($metadata->has('foo'));
        self::assertEquals('bar', $metadata->get('foo'));
        self::assertEquals('{"foo":"bar"}', json_encode($metadata));

        try {
            $metadata->get('bar');
        } catch (\InvalidArgumentException $e) {
            self::assertEquals('The value with key "bar" does not exist.', $e->getMessage());
        }
    }
}
