<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Core\Tests\Client\Rule;

use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\Client\Rule;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\Scope\NoScopePolicy;
use OAuth2Framework\Component\Server\Core\Scope\ScopePolicyManager;
use PHPUnit\Framework\TestCase;

/**
 * @group Rule
 */
final class ScopePolicyRuleTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "scope_policy" must be a string.
     */
    public function theParameterMustBeAString()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'scope_policy' => ['foo'],
        ]);
        $rule = $this->getScopePolicyRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The scope policy "foo" is not supported.
     */
    public function theScopePolicyIsNotSupported()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'scope_policy' => 'foo',
        ]);
        $rule = $this->getScopePolicyRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterIsValid()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'scope_policy' => 'none',
        ]);
        $rule = $this->getScopePolicyRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
        self::assertTrue($validatedParameters->has('scope_policy'));
        self::assertEquals('none', $validatedParameters->get('scope_policy'));
    }

    /**
     * @return callable
     */
    private function getCallable(): callable
    {
        return function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {
            return $validatedParameters;
        };
    }

    /**
     * @return Rule\ScopePolicyRule
     */
    private function getScopePolicyRule(): Rule\ScopePolicyRule
    {
        $scopePolicyManager = new ScopePolicyManager();
        $scopePolicyManager->add(new NoScopePolicy());

        return new Rule\ScopePolicyRule($scopePolicyManager);
    }
}
