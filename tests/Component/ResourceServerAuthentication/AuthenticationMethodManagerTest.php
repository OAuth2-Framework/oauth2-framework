<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ResourceServerAuthentication;

use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class AuthenticationMethodManagerTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericCalls(): void
    {
        static::assertSame([
            'none',
            'client_secret_post',
            'client_secret_basic',
            'client_secret_jwt',
            'private_key_jwt',
        ], $this->getAuthenticationMethodManager()
            ->list());
        static::assertCount(4, $this->getAuthenticationMethodManager()->all());
        static::assertSame(
            ['Basic realm="My Service",charset="UTF-8"'],
            $this->getAuthenticationMethodManager()
                ->getSchemesParameters()
        );
    }
}
