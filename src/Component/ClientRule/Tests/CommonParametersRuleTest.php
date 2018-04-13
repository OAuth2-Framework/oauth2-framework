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

namespace OAuth2Framework\Component\ClientRule\Tests;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\ClientRule;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @group Tests
 */
class CommonParametersRuleTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter with key "client_uri" is not a valid URL.
     */
    public function aParameterIsNotAValidUrl()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'client_name' => 'Client name',
            'client_uri' => 'urn:foo:bar:OK',
        ]);
        $rule = new ClientRule\CommonParametersRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function testCommonParameterRule()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'client_name' => 'Client name',
            'client_name#fr' => 'Nom du client',
            'client_uri' => 'http://localhost/information',
            'logo_uri' => 'http://127.0.0.1:8000/logo.png',
            'tos_uri' => 'http://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/tos.html',
            'policy_uri' => 'http://localhost/policy',
        ]);
        $rule = new ClientRule\CommonParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        self::assertTrue($validatedParameters->has('client_name'));
        self::assertEquals('Client name', $validatedParameters->get('client_name'));
        self::assertTrue($validatedParameters->has('client_uri'));
        self::assertEquals('http://localhost/information', $validatedParameters->get('client_uri'));
        self::assertTrue($validatedParameters->has('logo_uri'));
        self::assertEquals('http://127.0.0.1:8000/logo.png', $validatedParameters->get('logo_uri'));
        self::assertTrue($validatedParameters->has('tos_uri'));
        self::assertEquals('http://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/tos.html', $validatedParameters->get('tos_uri'));
        self::assertTrue($validatedParameters->has('policy_uri'));
        self::assertEquals('http://localhost/policy', $validatedParameters->get('policy_uri'));
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
}
