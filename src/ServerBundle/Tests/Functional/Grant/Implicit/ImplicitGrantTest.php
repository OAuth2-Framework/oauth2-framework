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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group Implicit
 */
class ImplicitGrantTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!class_exists(ImplicitGrantType::class)) {
            $this->markTestSkipped('The component "oauth2-framework/implicit-grant" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theRequestHasNoGrantType()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'bar.bar']);
        $client->request('POST', '/token/get', [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals('{"error":"invalid_request","error_description":"The \"grant_type\" parameter is missing."}', $response->getContent());
    }

    /**
     * @test
     */
    public function theImplicitGrantTypeCannotBeCalledFromTheTokenEndpoint()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'bar.bar']);
        $client->request('POST', '/token/get', ['grant_type' => 'implicit'], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('{"error":"invalid_grant","error_description":"The implicit grant type cannot be called from the token endpoint."}', $response->getContent());
    }
}
