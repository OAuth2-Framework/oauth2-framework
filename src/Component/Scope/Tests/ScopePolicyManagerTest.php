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

namespace OAuth2Framework\Component\Scope\Tests;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Scope\Checker;
use OAuth2Framework\Component\Scope\Policy\DefaultScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ErrorScopePolicy;
use OAuth2Framework\Component\Scope\Policy\NoScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use PHPUnit\Framework\TestCase;

/**
 * @group ScopePolicyManager
 */
final class ScopePolicyManagerTest extends TestCase
{
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
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            new UserAccountId('USER_ACCOUNT_ID')
        );

        $result = $this->getScopePolicyManager()->apply('foo', $client);
        static::assertEquals('foo', $result);
    }

    /**
     * @test
     */
    public function theClientHasNoScopePolicy()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            new UserAccountId('USER_ACCOUNT_ID')
        );

        $result = $this->getScopePolicyManager()->apply('', $client);
        static::assertEquals('', $result);
    }

    /**
     * @test
     */
    public function usingTheNonePolicy()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([
                'scope_policy' => 'none',
            ]),
            new UserAccountId('USER_ACCOUNT_ID')
        );

        $result = $this->getScopePolicyManager()->apply('', $client);
        static::assertEquals('', $result);
    }

    /**
     * @test
     */
    public function usingTheDefaultPolicyWithCustomDefaultScope()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([
                'scope_policy' => 'default',
                'default_scope' => 'openid profile',
            ]),
            new UserAccountId('USER_ACCOUNT_ID')
        );

        $result = $this->getScopePolicyManager()->apply('', $client);
        static::assertEquals('openid profile', $result);
    }

    /**
     * @test
     */
    public function usingTheDefaultPolicy()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([
                'scope_policy' => 'default',
            ]),
            new UserAccountId('USER_ACCOUNT_ID')
        );

        $result = $this->getScopePolicyManager()->apply('', $client);
        static::assertEquals('scope1 scope2', $result);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No scope was requested.
     */
    public function usingTheErrorPolicy()
    {
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([
                'scope_policy' => 'error',
            ]),
            new UserAccountId('USER_ACCOUNT_ID')
        );

        $this->getScopePolicyManager()->apply('', $client);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Scope "foo" appears more than once.
     */
    public function scopeIsUsedOnlyOnce()
    {
        Checker::checkUsedOnce('foo', 'foo bar');
        Checker::checkUsedOnce('foo', 'foo foo');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Scope contains illegal characters.
     */
    public function scopeCharsetIsNotValid()
    {
        Checker::checkCharset('foo bar');
        Checker::checkCharset('cookie café');
    }

    /**
     * @var null|ScopePolicyManager
     */
    private $scopePolicyManager = null;

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
