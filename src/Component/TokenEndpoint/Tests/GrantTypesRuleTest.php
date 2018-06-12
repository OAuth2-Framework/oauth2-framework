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

namespace OAuth2Framework\Component\TokenEndpoint\Tests;

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use OAuth2Framework\Component\TokenEndpoint\Rule\GrantTypesRule;
use PHPUnit\Framework\TestCase;

/**
 * @group Tests
 */
final class GrantTypesRuleTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!interface_exists(Rule::class)) {
            $this->markTestSkipped('The component "oauth2-framework/client-rule" is not installed.');
        }
    }

    /**
     * @test
     */
    public function testGrantTypesSetAsAnEmptyArray()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([]);
        $rule = $this->getGrantTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        self::assertTrue($validatedParameters->has('grant_types'));
        self::assertEquals([], $validatedParameters->get('grant_types'));
    }

    /**
     * @test
     */
    public function testGrantTypesCorrectlyDefined()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'grant_types' => ['authorization_code'],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['code']]);
        $rule = $this->getGrantTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());

        self::assertTrue($validatedParameters->has('grant_types'));
        self::assertEquals(['authorization_code'], $validatedParameters->get('grant_types'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "grant_types" must be an array of strings.
     */
    public function theGrantTypeParameterMustBeAnArray()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'grant_types' => 'hello',
        ]);
        $rule = $this->getGrantTypesRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "grant_types" must be an array of strings.
     */
    public function theGrantTypeParameterMustBeAnArrayOfStrings()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'grant_types' => [123],
        ]);
        $rule = $this->getGrantTypesRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theAssociatedResponseTypesAreSet()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'grant_types' => ['authorization_code'],
        ]);
        $validatedParameters = DataBag::create(['response_types' => ['code id_token token']]);
        $rule = $this->getGrantTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());

        self::assertTrue($validatedParameters->has('grant_types'));
        self::assertEquals(['authorization_code'], $validatedParameters->get('grant_types'));
        self::assertTrue($validatedParameters->has('response_types'));
        self::assertEquals(['code id_token token'], $validatedParameters->get('response_types'));
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
     * @var null|GrantTypesRule
     */
    private $grantTypesRule = null;

    /**
     * @return GrantTypesRule
     */
    private function getGrantTypesRule(): GrantTypesRule
    {
        if (null == $this->grantTypesRule) {
            $authorizationCodeGrantType = $this->prophesize(GrantType::class);
            $authorizationCodeGrantType->name()->willReturn('authorization_code');
            $authorizationCodeGrantType->associatedResponseTypes()->willReturn(['code']);

            $grantTypeManager = new GrantTypeManager();
            $grantTypeManager->add($authorizationCodeGrantType->reveal());
            $this->grantTypesRule = new GrantTypesRule(
                $grantTypeManager
            );
        }

        return $this->grantTypesRule;
    }
}
