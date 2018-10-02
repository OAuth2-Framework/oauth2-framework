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

namespace OAuth2Framework\ServerBundle\Tests\Functional\Grant\AuthorizationCode;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeGrantType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group AuthorizationCode
 */
class AuthorizationEndpointTest extends WebTestCase
{
    protected function setUp()
    {
        if (!\class_exists(AuthorizationCodeGrantType::class)) {
            static::markTestSkipped('The component "oauth2-framework/authorization-code-grant" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theRequestHasNoGrantType()
    {
        $client = static::createClient();
        $client->request('GET', '/authorize', [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        dump($response->getContent());
    }
}
