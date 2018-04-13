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
    public function theMetadataEndpointIsAvailable()
    {
        $client = static::createClient([], ['HTTP_HOST' => 'foo.foo.bar']);
        $client->request('GET', '/.well-known/openid-configuration', [], [], ['HTTPS' => 'on']);
        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json; charset=UTF-8', $response->headers->get('content-type'));
        $content = json_decode($response->getContent(), true);
        self::assertInternalType('array', $content);
    }
}
