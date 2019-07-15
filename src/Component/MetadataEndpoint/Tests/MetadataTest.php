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

namespace OAuth2Framework\Component\MetadataEndpoint\Tests;

use OAuth2Framework\Component\MetadataEndpoint\Metadata;
use PHPUnit\Framework\TestCase;

/**
 * @group Compiler
 *
 * @internal
 * @coversNothing
 */
final class MetadataTest extends TestCase
{
    /**
     * @test
     */
    public function genericObjectMethods()
    {
        $metadata = new Metadata();
        static::assertFalse($metadata->has('foo'));
        $metadata->set('foo', 'bar');
        static::assertTrue($metadata->has('foo'));
        static::assertEquals('bar', $metadata->get('foo'));

        try {
            $metadata->get('bar');
        } catch (\InvalidArgumentException $e) {
            static::assertEquals('The value with key "bar" does not exist.', $e->getMessage());
        }
    }
}
