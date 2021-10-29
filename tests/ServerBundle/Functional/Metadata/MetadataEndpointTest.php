<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\ServerBundle\Functional\Metadata;

use const JSON_THROW_ON_ERROR;
use OAuth2Framework\Component\MetadataEndpoint\MetadataEndpoint;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class MetadataEndpointTest extends WebTestCase
{
    protected function setUp(): void
    {
        if (! class_exists(MetadataEndpoint::class)) {
            static::markTestSkipped('The component "oauth2-framework/metadata-endpoint" is not installed.');
        }
        parent::setUp();
    }

    /**
     * @test
     */
    public function theMetadataEndpointIsAvailable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/.well-known/openid-configuration', [], [], [
            'HTTPS' => 'on',
        ]);
        $response = $client->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('application/json; charset=UTF-8', $response->headers->get('content-type'));
        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        static::assertIsArray($content);
    }
}
