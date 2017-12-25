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
final class ApplicationTypeParameterRuleTest extends TestCase
{
    /**
     * @test
     */
    public function testApplicationTypeParameterRuleSetAsDefault()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([]);
        $rule = new Rule\ApplicationTypeParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        self::assertTrue($validatedParameters->has('application_type'));
        self::assertEquals('web', $validatedParameters->get('application_type'));
    }

    /**
     * @test
     */
    public function testApplicationTypeParameterRuleDefineInParameters()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'application_type' => 'native',
        ]);
        $rule = new Rule\ApplicationTypeParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        self::assertTrue($validatedParameters->has('application_type'));
        self::assertEquals('native', $validatedParameters->get('application_type'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "application_type" must be either "native" or "web".
     */
    public function testApplicationTypeParameterRule()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'application_type' => 'foo',
        ]);
        $rule = new Rule\ApplicationTypeParametersRule();
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
}
