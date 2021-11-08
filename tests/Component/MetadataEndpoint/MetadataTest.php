<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\MetadataEndpoint;

use InvalidArgumentException;
use OAuth2Framework\Component\MetadataEndpoint\Metadata;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class MetadataTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericObjectMethods(): void
    {
        $metadata = new Metadata();
        static::assertFalse($metadata->has('foo'));
        $metadata->set('foo', 'bar');
        static::assertTrue($metadata->has('foo'));
        static::assertSame('bar', $metadata->get('foo'));

        try {
            $metadata->get('bar');
        } catch (InvalidArgumentException $e) {
            static::assertSame('The value with key "bar" does not exist.', $e->getMessage());
        }
    }
}
