<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ResourceServerAuthentication;

use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMethod;
use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMethodManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class AuthenticationMethodManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function genericCalls(): void
    {
        $method = $this->prophesize(AuthenticationMethod::class);
        $method->getSupportedMethods()
            ->willReturn(['foo'])
        ;
        $method->getSchemesParameters()
            ->willReturn(['Basic realm="Realm",charset="UTF-8"'])
        ;
        $manager = new AuthenticationMethodManager();
        $manager
            ->add($method->reveal())
        ;
        static::assertTrue($manager->has('foo'));
        static::assertSame(['foo'], $manager->list());
        static::assertInstanceOf(AuthenticationMethod::class, $manager->get('foo'));
        static::assertCount(1, $manager->all());
        static::assertSame(['Basic realm="Realm",charset="UTF-8"'], $manager->getSchemesParameters());
    }
}
