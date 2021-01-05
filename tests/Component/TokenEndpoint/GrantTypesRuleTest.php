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

namespace OAuth2Framework\Tests\Component\TokenEndpoint;

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use OAuth2Framework\Component\TokenEndpoint\Rule\GrantTypesRule;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @group Tests
 *
 * @internal
 */
final class GrantTypesRuleTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var null|GrantTypesRule
     */
    private $grantTypesRule;

    protected function setUp(): void
    {
        if (!interface_exists(Rule::class)) {
            static::markTestSkipped('The component "oauth2-framework/client-rule" is not installed.');
        }
    }

    /**
     * @test
     */
    public function grantTypesSetAsAnEmptyArray()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([]);
        $rule = $this->getGrantTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('grant_types'));
        static::assertEquals([], $validatedParameters->get('grant_types'));
    }

    /**
     * @test
     */
    public function grantTypesCorrectlyDefined()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'grant_types' => ['authorization_code'],
        ]);
        $validatedParameters = new DataBag(['response_types' => ['code']]);
        $rule = $this->getGrantTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());

        static::assertTrue($validatedParameters->has('grant_types'));
        static::assertEquals(['authorization_code'], $validatedParameters->get('grant_types'));
    }

    /**
     * @test
     */
    public function theGrantTypeParameterMustBeAnArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "grant_types" must be an array of strings.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'grant_types' => 'hello',
        ]);
        $rule = $this->getGrantTypesRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theGrantTypeParameterMustBeAnArrayOfStrings()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "grant_types" must be an array of strings.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'grant_types' => [123],
        ]);
        $rule = $this->getGrantTypesRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theAssociatedResponseTypesAreSet()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'grant_types' => ['authorization_code'],
        ]);
        $validatedParameters = new DataBag(['response_types' => ['code id_token token']]);
        $rule = $this->getGrantTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());

        static::assertTrue($validatedParameters->has('grant_types'));
        static::assertEquals(['authorization_code'], $validatedParameters->get('grant_types'));
        static::assertTrue($validatedParameters->has('response_types'));
        static::assertEquals(['code id_token token'], $validatedParameters->get('response_types'));
    }

    private function getCallable(): RuleHandler
    {
        return new RuleHandler(function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {
            return $validatedParameters;
        });
    }

    private function getGrantTypesRule(): GrantTypesRule
    {
        if (null === $this->grantTypesRule) {
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
