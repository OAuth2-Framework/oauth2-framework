<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint;

use InvalidArgumentException;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class ResponseTypeManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function basicCalls(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The response type "bar" is not supported.');
        $manager = new ResponseTypeManager();

        $type = $this->prophesize(ResponseType::class);
        $type->name()
            ->willReturn('foo')
        ;

        $manager->add($type->reveal());

        static::assertTrue($manager->has('foo'));
        static::assertFalse($manager->has('bar'));
        static::assertInstanceOf(ResponseType::class, $manager->get('foo'));
        static::assertSame(['foo'], $manager->list());
        static::assertCount(1, $manager->all());

        $manager->get('bar');
    }
}
