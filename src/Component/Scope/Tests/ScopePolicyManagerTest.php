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
use OAuth2Framework\Component\Scope\Checker;
use OAuth2Framework\Component\Scope\Policy\DefaultScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ErrorScopePolicy;
use OAuth2Framework\Component\Scope\Policy\NoScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;

/**
 * @group ScopePolicyManager
 */
class ScopePolicyManagerTest extends TestCase
{
    /**
     * @test
     */
    public function genericCalls()
    {
        self::assertTrue($this->getScopePolicyManager()->has('error'));
        self::assertFalse($this->getScopePolicyManager()->has('foo'));
        self::assertEquals(['none', 'default', 'error'], $this->getScopePolicyManager()->all());
    }

    /**
     * @test
     */
    public function scopesAreProvided()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $result = $this->getScopePolicyManager()->apply('foo', $client);
        self::assertEquals('foo', $result);
    }

    /**
     * @test
     */
    public function theClientHasNoScopePolicy()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $result = $this->getScopePolicyManager()->apply('', $client);
        self::assertEquals('', $result);
    }

    /**
     * @test
     */
    public function testUsingTheNonePolicy()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'scope_policy' => 'none',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $result = $this->getScopePolicyManager()->apply('', $client);
        self::assertEquals('', $result);
    }

    /**
     * @test
     */
    public function testUsingTheDefaultPolicyWithCustomDefaultScope()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'scope_policy' => 'default',
                'default_scope' => 'openid profile',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $result = $this->getScopePolicyManager()->apply('', $client);
        self::assertEquals('openid profile', $result);
    }

    /**
     * @test
     */
    public function testUsingTheDefaultPolicy()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'scope_policy' => 'default',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $result = $this->getScopePolicyManager()->apply('', $client);
        self::assertEquals('scope1 scope2', $result);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No scope was requested.
     */
    public function testUsingTheErrorPolicy()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'scope_policy' => 'error',
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );

        $this->getScopePolicyManager()->apply('', $client);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Scope "foo" appears more than once.
     */
    public function testScopeIsUsedOnlyOnce()
    {
        Checker::checkUsedOnce('foo', 'foo bar');
        Checker::checkUsedOnce('foo', 'foo foo');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Scope contains illegal characters.
     */
    public function testScopeCharsetIsNotValid()
    {
        Checker::checkCharset('foo bar');
        Checker::checkCharset('cookie café');
    }

    /**
     * @var null|ScopePolicyManager
     */
    private $scopePolicyManager = null;

    /**
     * @return ScopePolicyManager
     */
    private function getScopePolicyManager(): ScopePolicyManager
    {
        if (null === $this->scopePolicyManager) {
            $this->scopePolicyManager = new ScopePolicyManager();
            $this->scopePolicyManager
                ->add(new NoScopePolicy())
                ->add(new DefaultScopePolicy('scope1 scope2'))
                ->add(new ErrorScopePolicy());
        }

        return $this->scopePolicyManager;
    }
}
