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

namespace OAuth2Framework\Component\Scope\Tests;

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Scope\Policy\NoScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Scope\Rule\ScopePolicyRule;
use PHPUnit\Framework\TestCase;

/**
 * @group Tests
 *
 * @internal
 * @coversNothing
 */
final class ScopePolicyRuleTest extends TestCase
{
    /**
     * @inheritdoc}
     */
    protected function setUp(): void
    {
        if (!interface_exists(Rule::class)) {
            static::markTestSkipped('The component "oauth2-framework/client" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theParameterMustBeAString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "scope_policy" must be a string.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'scope_policy' => ['foo'],
        ]);
        $rule = $this->getScopePolicyRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theScopePolicyIsNotSupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The scope policy "foo" is not supported.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'scope_policy' => 'foo',
        ]);
        $rule = $this->getScopePolicyRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterIsValid()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'scope_policy' => 'none',
        ]);
        $rule = $this->getScopePolicyRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
        static::assertTrue($validatedParameters->has('scope_policy'));
        static::assertEquals('none', $validatedParameters->get('scope_policy'));
    }

    private function getCallable(): RuleHandler
    {
        return new RuleHandler(function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {
            return $validatedParameters;
        });
    }

    private function getScopePolicyRule(): ScopePolicyRule
    {
        $scopePolicyManager = new ScopePolicyManager();
        $scopePolicyManager->add(new NoScopePolicy());

        return new ScopePolicyRule($scopePolicyManager);
    }
}
