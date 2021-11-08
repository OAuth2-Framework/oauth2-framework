<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Scope;

use InvalidArgumentException;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Scope\Checker;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

/**
 * @internal
 */
final class ScopePolicyManagerTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function genericCalls(): void
    {
        static::assertTrue($this->getScopePolicyManager()->has('error'));
        static::assertFalse($this->getScopePolicyManager()->has('foo'));
        static::assertSame(['none', 'default', 'error'], $this->getScopePolicyManager()->all());
    }

    /**
     * @test
     * @dataProvider useCases
     */
    public function scopePolicyIsCorrectlyApplied(
        array $clientConfiguration,
        string $requestedScope,
        ?string $expectedScope,
        ?string $expectedException,
        ?string $expectedExceptionMessage
    ): void {
        if ($expectedException !== null) {
            static::expectException($expectedException);
        }

        if ($expectedExceptionMessage !== null) {
            static::expectExceptionMessage($expectedExceptionMessage);
        }

        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create($clientConfiguration),
            UserAccountId::create('john.1')
        );

        $result = $this->getScopePolicyManager()
            ->apply($requestedScope, $client)
        ;
        if ($expectedScope !== null) {
            static::assertSame($expectedScope, $result);
        }
    }

    /**
     * @test
     */
    public function scopeIsUsedOnlyOnce(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scope "foo" appears more than once.');
        Checker::checkUsedOnce('foo', 'foo bar');
        Checker::checkUsedOnce('foo', 'foo foo');
    }

    /**
     * @test
     */
    public function scopeCharsetIsNotValid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scope contains illegal characters.');
        Checker::checkCharset('foo bar');
        Checker::checkCharset('cookie café');
    }

    public function useCases(): array
    {
        return [
            [[], '', '', null, null],
            [
                [
                    'scope_policy' => 'none',
                ],
                '',
                '',
                null,
                null,
            ],
            [
                [
                    'scope_policy' => 'none',
                ],
                'scope1 scope2',
                'scope1 scope2',
                null,
                null,
            ],
            [
                [
                    'scope_policy' => 'default',
                ],
                '',
                'scope1 scope2',
                null,
                null,
            ],
            [
                [
                    'scope_policy' => 'default',
                ],
                'openid',
                'openid',
                null,
                null,
            ],
            [
                [
                    'scope_policy' => 'error',
                ],
                '',
                null,
                InvalidArgumentException::class,
                'No scope was requested',
            ],
            [
                [
                    'scope_policy' => 'error',
                ],
                'openid',
                'openid',
                null,
                null,
            ],
        ];
    }
}
