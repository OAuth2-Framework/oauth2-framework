<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint;

use InvalidArgumentException;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class ResponseTypeManagerTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function basicCalls(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response type "bar" is not supported.');
        $manager = $this->getResponseTypeManager();

        static::assertTrue($manager->has('code'));
        static::assertFalse($manager->has('bar'));
        static::assertSame(['token', 'none', 'code'], $manager->list());
        static::assertCount(3, $manager->all());

        $manager->get('bar');
    }
}
