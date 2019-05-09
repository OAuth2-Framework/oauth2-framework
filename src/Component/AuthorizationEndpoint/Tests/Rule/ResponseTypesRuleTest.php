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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Tests\Rule;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\Rule\ResponseTypesRule;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @group Tests
 */
final class ResponseTypesRuleTest extends TestCase
{
    protected function setUp(): void
    {
        if (!\interface_exists(Rule::class)) {
            static::markTestSkipped('The component "oauth2-framework/client-rule" is not installed.');
        }
    }

    /**
     * @test
     */
    public function responseTypesSetAsAnEmptyArray()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([]);
        $rule = $this->getResponseTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('response_types'));
        static::assertEquals([], $validatedParameters->get('response_types'));
    }

    /**
     * @test
     */
    public function responseTypesCorrectlyDefined()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'response_types' => ['code', 'id_token'],
        ]);
        $validatedParameters = new DataBag(['grant_types' => ['authorization_code']]);
        $rule = $this->getResponseTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());

        static::assertTrue($validatedParameters->has('response_types'));
        static::assertEquals(['code', 'id_token'], $validatedParameters->get('response_types'));
    }

    /**
     * @test
     */
    public function theResponseTypeParameterMustBeAnArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "response_types" must be an array of strings.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'response_types' => 'hello',
        ]);
        $rule = $this->getResponseTypesRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theResponseTypeParameterMustBeAnArrayOfStrings()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "response_types" must be an array of strings.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'response_types' => [123],
        ]);
        $rule = $this->getResponseTypesRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    private function getCallable(): RuleHandler
    {
        return new RuleHandler(function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {
            return $validatedParameters;
        });
    }

    /**
     * @var ResponseTypesRule|null
     */
    private $responseTypesRule;

    private function getResponseTypesRule(): ResponseTypesRule
    {
        if (null === $this->responseTypesRule) {
            $codeResponseType = $this->prophesize(ResponseType::class);
            $codeResponseType->name()->willReturn('code');
            $codeResponseType->associatedGrantTypes()->willReturn(['authorization_code']);

            $idTokenResponseType = $this->prophesize(ResponseType::class);
            $idTokenResponseType->name()->willReturn('id_token');
            $idTokenResponseType->associatedGrantTypes()->willReturn([]);

            $responseTypeManager = new ResponseTypeManager();
            $responseTypeManager->add($codeResponseType->reveal());
            $responseTypeManager->add($idTokenResponseType->reveal());
            $this->responseTypesRule = new ResponseTypesRule($responseTypeManager);
        }

        return $this->responseTypesRule;
    }
}
