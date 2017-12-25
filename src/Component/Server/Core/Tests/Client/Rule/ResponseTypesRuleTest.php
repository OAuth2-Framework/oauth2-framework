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

use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseTypeManager;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\Client\Rule;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @group Rule
 */
final class ResponseTypesRuleTest extends TestCase
{
    /**
     * @test
     */
    public function testResponseTypesSetAsAnEmptyArray()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([]);
        $rule = $this->getResponseTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        self::assertTrue($validatedParameters->has('response_types'));
        self::assertEquals([], $validatedParameters->get('response_types'));
    }

    /**
     * @test
     */
    public function testResponseTypesCorrectlyDefined()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'response_types' => ['code', 'code id_token'],
        ]);
        $validatedParameters = DataBag::create(['grant_types' => ['authorization_code']]);
        $rule = $this->getResponseTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());

        self::assertTrue($validatedParameters->has('response_types'));
        self::assertEquals(['code', 'code id_token'], $validatedParameters->get('response_types'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "response_types" must be an array of strings.
     */
    public function theResponseTypeParameterMustBeAnArray()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'response_types' => 'hello',
        ]);
        $rule = $this->getResponseTypesRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "response_types" must be an array of strings.
     */
    public function theResponseTypeParameterMustBeAnArrayOfStrings()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'response_types' => [123],
        ]);
        $rule = $this->getResponseTypesRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The response type "code" requires the following grant type(s): authorization_code.
     */
    public function theAssociatedResponseTypesAreMissing()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'response_types' => ['code', 'code id_token'],
        ]);
        $rule = $this->getResponseTypesRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
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
     * @var null|Rule\ResponseTypesRule
     */
    private $responseTypesRule = null;

    /**
     * @return Rule\ResponseTypesRule
     */
    private function getResponseTypesRule(): Rule\ResponseTypesRule
    {
        if (null == $this->responseTypesRule) {
            $codeResponseType = $this->prophesize(ResponseType::class);
            $codeResponseType->getResponseType()->willReturn('code');
            $codeResponseType->getAssociatedGrantTypes()->willReturn(['authorization_code']);

            $idTokenResponseType = $this->prophesize(ResponseType::class);
            $idTokenResponseType->getResponseType()->willReturn('id_token');
            $idTokenResponseType->getAssociatedGrantTypes()->willReturn([]);

            $responseTypeManager = new ResponseTypeManager();
            $responseTypeManager
                ->add($codeResponseType->reveal())
                ->add($idTokenResponseType->reveal());
            $this->responseTypesRule = new Rule\ResponseTypesRule($responseTypeManager);
        }

        return $this->responseTypesRule;
    }
}
