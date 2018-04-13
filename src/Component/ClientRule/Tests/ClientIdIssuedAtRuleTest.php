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
class ClientIdIssuedAtRuleTest extends TestCase
{
    /**
     * @test
     */
    public function testClientIdIssuedAtRuleSetAsDefault()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([]);
        $rule = new ClientRule\ClientIdIssuedAtRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        self::assertTrue($validatedParameters->has('client_id_issued_at'));
        self::assertInternalType('integer', $validatedParameters->get('client_id_issued_at'));
    }

    /**
     * @test
     */
    public function testClientIdIssuedAtRuleDefineInParameters()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'client_id_issued_at' => time() - 1000,
        ]);
        $rule = new ClientRule\ClientIdIssuedAtRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        self::assertTrue($validatedParameters->has('client_id_issued_at'));
        self::assertInternalType('integer', $validatedParameters->get('client_id_issued_at'));
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
