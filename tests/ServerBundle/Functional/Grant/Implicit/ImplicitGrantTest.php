<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\ServerBundle\Functional\Grant\Implicit;

use OAuth2Framework\Component\ImplicitGrant\ImplicitGrantType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class ImplicitGrantTest extends WebTestCase
{
    protected function setUp(): void
    {
        if (! class_exists(ImplicitGrantType::class)) {
            static::markTestSkipped('The component "oauth2-framework/implicit-grant" is not installed.');
        }
        parent::setUp();
    }

    /**
     * @test
     */
    public function theRequestHasNoGrantType(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(
            '{"error":"invalid_request","error_description":"The \"grant_type\" parameter is missing."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function theImplicitGrantTypeCannotBeCalledFromTheTokenEndpoint(): void
    {
        $client = static::createClient();
        $client->request('POST', '/token/get', [
            'grant_type' => 'implicit',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_grant","error_description":"The implicit grant type cannot be called from the token endpoint."}',
            $response->getContent()
        );
    }
}
