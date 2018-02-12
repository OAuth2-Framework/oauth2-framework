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

namespace OAuth2Framework\Bundle\Tests\Functional\Metadata;

use OAuth2Framework\Component\MetadataEndpoint\MetadataEndpoint;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group Bundle
 * @group Functional
 * @group Grant
 * @group Compiler
 */
class MetadataEndpointTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!class_exists(MetadataEndpoint::class)) {
            $this->markTestSkipped('The component "oauth2-framework/metadata-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theClientIsNotAuthenticated()
    {
        $client = static::createClient();
        $client->request('GET', '/.well-known/openid-configuration', [], [], ['HTTPS' => 'on']);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json; charset=UTF-8', $response->headers->get('content-type'));
        $content = json_decode($response->getContent(), true);
        self::assertArrayHasKey('token_endpoint_auth_methods_supported', $content);
        self::assertArrayHasKey('token_endpoint', $content);
        self::assertArrayHasKey('token_introspection_endpoint', $content);
        self::assertArrayHasKey('token_revocation_endpoint', $content);
        self::assertArrayHasKey('issuer', $content);
        self::assertArrayHasKey('service_documentation', $content);
        self::assertArrayHasKey('op_policy_uri', $content);
        self::assertArrayHasKey('op_tos_uri', $content);
    }
}
