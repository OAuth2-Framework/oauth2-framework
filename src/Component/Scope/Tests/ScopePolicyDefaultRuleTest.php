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

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Scope\Rule\ScopePolicyDefaultRule;
use PHPUnit\Framework\TestCase;

/**
 * @group Tests
 */
final class ScopePolicyDefaultRuleTest extends TestCase
{
    /**
     * @inheritdoc}
     */
    protected function setUp()
    {
        if (!\interface_exists(Rule::class)) {
            static::markTestSkipped('The component "oauth2-framework/client" is not installed.');
        }
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "default_scope" parameter must be a string.
     */
    public function theParameterMustBeAString()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'default_scope' => ['foo'],
        ]);
        $rule = new ScopePolicyDefaultRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid characters found in the "default_scope" parameter.
     */
    public function theParameterContainsForbiddenCharacters()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'default_scope' => 'coffee, café',
        ]);
        $rule = new ScopePolicyDefaultRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterIsValid()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'default_scope' => 'coffee cream',
        ]);
        $rule = new ScopePolicyDefaultRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
        static::assertTrue($validatedParameters->has('default_scope'));
        static::assertEquals('coffee cream', $validatedParameters->get('default_scope'));
    }

    private function getCallable(): RuleHandler
    {
        return new RuleHandler(function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {
            return $validatedParameters;
        });
    }
}
