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

namespace OAuth2Framework\SecurityBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group SecurityBundle
 */
class SecurityBundleTest extends WebTestCase
{
    /**
     * @test
     */
    public function aClientAccessTokenAnApiEndpointWithoutAccessToken()
    {
        $client = static::createClient();
        $client->request('GET', '/api/hello/World');
        $response = $client->getResponse();
        self::assertEquals(401, $response->getStatusCode());
        self::assertEquals('', $response->getContent());
    }
}
