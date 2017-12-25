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
final class ScopePolicyDefaultRuleTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "default_scope" parameter must be a string.
     */
    public function theParameterMustBeAString()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'default_scope' => ['foo'],
        ]);
        $rule = new Rule\ScopePolicyDefaultRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid characters found in the "default_scope" parameter.
     */
    public function theParameterContainsForbiddenCharacters()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'default_scope' => 'coffee, café',
        ]);
        $rule = new Rule\ScopePolicyDefaultRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterIsValid()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'default_scope' => 'coffee cream',
        ]);
        $rule = new Rule\ScopePolicyDefaultRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
        self::assertTrue($validatedParameters->has('default_scope'));
        self::assertEquals('coffee cream', $validatedParameters->get('default_scope'));
    }

    /**
     * @return callable
     */
    private function getCallable(): callable
    {
        return function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {return $validatedParameters;};
    }
}
