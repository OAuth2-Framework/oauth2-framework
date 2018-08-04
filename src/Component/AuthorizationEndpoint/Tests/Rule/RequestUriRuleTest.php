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

use OAuth2Framework\Component\AuthorizationEndpoint\Rule\RequestUriRule;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @group Tests
 */
final class RequestUriRuleTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!\interface_exists(Rule::class)) {
            static::markTestSkipped('The component "oauth2-framework/client-rule" is not installed.');
        }
    }

    /**
     * @test
     */
    public function noResponseType()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([]);
        $rule = new RequestUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
        static::assertFalse($validatedParameters->has('request_uris'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "request_uris" must be a list of URI.
     */
    public function theParameterMustBeAnArray()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'request_uris' => 'hello',
        ]);
        $rule = new RequestUriRule();
        $validatedParameters = new DataBag([
            'response_types' => ['code'],
        ]);
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "request_uris" must be a list of URI.
     */
    public function theParameterMustBeAnArrayOfString()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'request_uris' => [123],
        ]);
        $rule = new RequestUriRule();
        $validatedParameters = new DataBag([
            'response_types' => ['code'],
        ]);
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "request_uris" must be a list of URI.
     */
    public function theParameterMustBeAnArrayOfUris()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'request_uris' => ['hello'],
        ]);
        $rule = new RequestUriRule();
        $validatedParameters = new DataBag([
            'response_types' => ['code'],
        ]);
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterIsValid()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'request_uris' => ['https://foo.com/bar'],
        ]);
        $rule = new RequestUriRule();
        $validatedParameters = new DataBag([
            'response_types' => ['code'],
        ]);
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        static::assertTrue($validatedParameters->has('request_uris'));
        static::assertEquals(['https://foo.com/bar'], $validatedParameters->get('request_uris'));
    }

    private function getCallable(): callable
    {
        return function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {
            return $validatedParameters;
        };
    }
}
