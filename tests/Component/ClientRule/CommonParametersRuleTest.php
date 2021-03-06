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

namespace OAuth2Framework\Tests\Component\ClientRule;

use OAuth2Framework\Component\ClientRule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @group Tests
 *
 * @internal
 */
final class CommonParametersRuleTest extends TestCase
{
    /**
     * @test
     */
    public function aParameterIsNotAValidUrl()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter with key "client_uri" is not a valid URL.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'client_name' => 'Client name',
            'client_uri' => 'urn:foo:bar:OK',
        ]);
        $rule = new ClientRule\CommonParametersRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function commonParameterRule()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'client_name' => 'Client name',
            'client_name#fr' => 'Nom du client',
            'client_uri' => 'http://localhost/information',
            'logo_uri' => 'http://127.0.0.1:8000/logo.png',
            'tos_uri' => 'http://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/tos.html',
            'policy_uri' => 'http://localhost/policy',
        ]);
        $rule = new ClientRule\CommonParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('client_name'));
        static::assertEquals('Client name', $validatedParameters->get('client_name'));
        static::assertTrue($validatedParameters->has('client_uri'));
        static::assertEquals('http://localhost/information', $validatedParameters->get('client_uri'));
        static::assertTrue($validatedParameters->has('logo_uri'));
        static::assertEquals('http://127.0.0.1:8000/logo.png', $validatedParameters->get('logo_uri'));
        static::assertTrue($validatedParameters->has('tos_uri'));
        static::assertEquals('http://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/tos.html', $validatedParameters->get('tos_uri'));
        static::assertTrue($validatedParameters->has('policy_uri'));
        static::assertEquals('http://localhost/policy', $validatedParameters->get('policy_uri'));
    }

    private function getCallable(): ClientRule\RuleHandler
    {
        return new ClientRule\RuleHandler(function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {
            return $validatedParameters;
        });
    }
}
