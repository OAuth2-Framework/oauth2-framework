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

namespace OAuth2Framework\ServerBundle\Tests\Functional\Grant\Implicit;

use OAuth2Framework\Component\ImplicitGrant\ImplicitGrantType;
use OAuth2Framework\ServerBundle\Tests\Functional\DatabaseTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group Implicit
 */
class ImplicitGrantTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        if (!\class_exists(ImplicitGrantType::class)) {
            static::markTestSkipped('The component "oauth2-framework/implicit-grant" is not installed.');
        }
        parent::setUp();
    }

    /**
     * @test
     */
    public function theRequestHasNoGrantType()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals('{"error":"invalid_request","error_description":"The \"grant_type\" parameter is missing."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theImplicitGrantTypeCannotBeCalledFromTheTokenEndpoint()
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', ['grant_type' => 'implicit'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        static::assertEquals(400, $response->getStatusCode());
        static::assertEquals('{"error":"invalid_grant","error_description":"The implicit grant type cannot be called from the token endpoint."}', $response->getContent());
    }
}
