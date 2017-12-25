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
use PHPUnit\Framework\TestCase;

/**
 * @group Rule
 */
final class RequestUriRuleTest extends TestCase
{
    /**
     * @test
     */
    public function noResponseType()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([]);
        $rule = new Rule\RequestUriRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
        self::assertFalse($validatedParameters->has('request_uris'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "request_uris" must be a list of URI.
     */
    public function theParameterMustBeAnArray()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'request_uris' => 'hello',
        ]);
        $rule = new Rule\RequestUriRule();
        $validatedParameters = DataBag::create([
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
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'request_uris' => [123],
        ]);
        $rule = new Rule\RequestUriRule();
        $validatedParameters = DataBag::create([
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
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'request_uris' => ['hello'],
        ]);
        $rule = new Rule\RequestUriRule();
        $validatedParameters = DataBag::create([
            'response_types' => ['code'],
        ]);
        $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterIsValid()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'request_uris' => ['https://foo.com/bar'],
        ]);
        $rule = new Rule\RequestUriRule();
        $validatedParameters = DataBag::create([
            'response_types' => ['code'],
        ]);
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());
        self::assertTrue($validatedParameters->has('request_uris'));
        self::assertEquals(['https://foo.com/bar'], $validatedParameters->get('request_uris'));
    }

    /**
     * @return callable
     */
    private function getCallable(): callable
    {
        return function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {return $validatedParameters;};
    }
}
