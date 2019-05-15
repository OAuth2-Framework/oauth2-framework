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

namespace OAuth2Framework\ServerBundle\Tests\Functional\Metadata;

use OAuth2Framework\Component\MetadataEndpoint\MetadataEndpoint;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group Compiler
 */
class MetadataEndpointTest extends WebTestCase
{
    protected function setUp(): void
    {
        if (!\class_exists(MetadataEndpoint::class)) {
            static::markTestSkipped('The component "oauth2-framework/metadata-endpoint" is not installed.');
        }
        parent::setUp();
    }

    /**
     * @test
     */
    public function theMetadataEndpointIsAvailable()
    {
        $client = static::createClient();
        $client->request('GET', '/.well-known/openid-configuration', [], [], ['HTTPS' => 'on']);
        $response = $client->getResponse();
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('application/json; charset=UTF-8', $response->headers->get('content-type'));
        $content = \Safe\json_decode($response->getContent(), true);
        static::assertIsArray($content);
    }
}
