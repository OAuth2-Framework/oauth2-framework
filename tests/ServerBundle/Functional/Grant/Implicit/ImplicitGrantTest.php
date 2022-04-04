<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\ServerBundle\Functional\Grant\Implicit;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class ImplicitGrantTest extends WebTestCase
{
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
