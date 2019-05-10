<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\ClientRule\Tests;

use OAuth2Framework\Component\ClientRule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @group Tests
 */
final class ApplicationTypeParameterRuleTest extends TestCase
{
    /**
     * @test
     */
    public function applicationTypeParameterRuleSetAsDefault()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([]);
        $rule = new ClientRule\ApplicationTypeParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('application_type'));
        static::assertEquals('web', $validatedParameters->get('application_type'));
    }

    /**
     * @test
     */
    public function applicationTypeParameterRuleDefineInParameters()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'application_type' => 'native',
        ]);
        $rule = new ClientRule\ApplicationTypeParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('application_type'));
        static::assertEquals('native', $validatedParameters->get('application_type'));
    }

    /**
     * @test
     */
    public function applicationTypeParameterRule()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "application_type" must be either "native" or "web".');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'application_type' => 'foo',
        ]);
        $rule = new ClientRule\ApplicationTypeParametersRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    private function getCallable(): ClientRule\RuleHandler
    {
        return new ClientRule\RuleHandler(function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {
            return $validatedParameters;
        });
    }
}
