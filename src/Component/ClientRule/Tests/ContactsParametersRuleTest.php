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
final class ContactsParametersRuleTest extends TestCase
{
    /**
     * @test
     */
    public function theContactsParameterIsNotAnArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "contacts" must be a list of e-mail addresses.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'contacts' => 123,
        ]);
        $rule = new ClientRule\ContactsParametersRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theContactsParameterIsNotAnArrayOfEmailAddresses()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "contacts" must be a list of e-mail addresses.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'contacts' => [123],
        ]);
        $rule = new ClientRule\ContactsParametersRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theContactsParameterIsValid()
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'contacts' => [
                'foo@bar.com',
                'hello@you.com',
            ],
        ]);
        $rule = new ClientRule\ContactsParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('contacts'));
        static::assertEquals(['foo@bar.com', 'hello@you.com'], $validatedParameters->get('contacts'));
    }

    private function getCallable(): ClientRule\RuleHandler
    {
        return new ClientRule\RuleHandler(function (ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag {
            return $validatedParameters;
        });
    }
}
