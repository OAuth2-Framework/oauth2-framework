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

namespace OAuth2Framework\Tests\Component\Scope;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Scope\Checker;
use OAuth2Framework\Component\Scope\Policy\DefaultScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ErrorScopePolicy;
use OAuth2Framework\Component\Scope\Policy\NoScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @group ScopePolicyManager
 *
 * @internal
 */
final class ScopePolicyManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var null|ScopePolicyManager
     */
    private $scopePolicyManager;

    /**
     * @test
     */
    public function genericCalls()
    {
        static::assertTrue($this->getScopePolicyManager()->has('error'));
        static::assertFalse($this->getScopePolicyManager()->has('foo'));
        static::assertEquals(['none', 'default', 'error'], $this->getScopePolicyManager()->all());
    }

    /**
     * @test
     */
    public function scopesAreProvided()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));

        $result = $this->getScopePolicyManager()->apply('foo', $client->reveal());
        static::assertEquals('foo', $result);
    }

    /**
     * @test
     */
    public function theClientHasNoScopePolicy()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $client->has('scope_policy')->willReturn(false);

        $result = $this->getScopePolicyManager()->apply('', $client->reveal());
        static::assertEquals('', $result);
    }

    /**
     * @test
     */
    public function usingTheNonePolicy()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $client->has('scope_policy')->willReturn(true);
        $client->get('scope_policy')->willReturn('none');

        $result = $this->getScopePolicyManager()->apply('', $client->reveal());
        static::assertEquals('', $result);
    }

    /**
     * @test
     */
    public function usingTheDefaultPolicyWithCustomDefaultScope()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $client->has('scope_policy')->willReturn(true);
        $client->get('scope_policy')->willReturn('default');
        $client->has('default_scope')->willReturn(true);
        $client->get('default_scope')->willReturn('openid profile');

        $result = $this->getScopePolicyManager()->apply('', $client->reveal());
        static::assertEquals('openid profile', $result);
    }

    /**
     * @test
     */
    public function usingTheDefaultPolicy()
    {
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $client->has('scope_policy')->willReturn(true);
        $client->get('scope_policy')->willReturn('default');
        $client->has('default_scope')->willReturn(false);

        $result = $this->getScopePolicyManager()->apply('', $client->reveal());
        static::assertEquals('scope1 scope2', $result);
    }

    /**
     * @test
     */
    public function usingTheErrorPolicy()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No scope was requested.');
        $client = $this->prophesize(Client::class);
        $client->isPublic()->willReturn(false);
        $client->getPublicId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getClientId()->willReturn(new ClientId('CLIENT_ID'));
        $client->getOwnerId()->willReturn(new UserAccountId('USER_ACCOUNT_ID'));
        $client->has('scope_policy')->willReturn(true);
        $client->get('scope_policy')->willReturn('error');

        $this->getScopePolicyManager()->apply('', $client->reveal());
    }

    /**
     * @test
     */
    public function scopeIsUsedOnlyOnce()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Scope "foo" appears more than once.');
        Checker::checkUsedOnce('foo', 'foo bar');
        Checker::checkUsedOnce('foo', 'foo foo');
    }

    /**
     * @test
     */
    public function scopeCharsetIsNotValid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Scope contains illegal characters.');
        Checker::checkCharset('foo bar');
        Checker::checkCharset('cookie café');
    }

    private function getScopePolicyManager(): ScopePolicyManager
    {
        if (null === $this->scopePolicyManager) {
            $this->scopePolicyManager = new ScopePolicyManager();
            $this->scopePolicyManager->add(new NoScopePolicy());
            $this->scopePolicyManager->add(new DefaultScopePolicy('scope1 scope2'));
            $this->scopePolicyManager->add(new ErrorScopePolicy());
        }

        return $this->scopePolicyManager;
    }
}
