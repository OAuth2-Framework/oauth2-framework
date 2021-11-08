<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\WebFingerBundle\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class WebFingerEndpointTest extends WebTestCase
{
    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithoutResourceParameter(): void
    {
        $client = static::createClient([], [
            'HTTP_HOST' => 'my-service.com',
            'HTTP_PORT' => 443,
        ]);
        $client->request('GET', '/.well-known/webfinger', [
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(400, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"The parameter \"resource\" is mandatory."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAnInvalidResourceParameterBasedOnAnXRI(): void
    {
        $client = static::createClient([], [
            'HTTP_HOST' => 'my-service.com',
            'HTTP_PORT' => 443,
        ]);
        $client->request('GET', '/.well-known/webfinger', [
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => '@foo',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(404, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"The resource identified with \"@foo\" does not exist or is not supported by this server."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAnInvalidResourceParameterBasedOnAnAccount(): void
    {
        $client = static::createClient([], [
            'HTTP_HOST' => 'my-service.com',
            'HTTP_PORT' => 443,
        ]);
        $client->request('GET', '/.well-known/webfinger', [
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'acct:john@example.com',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(404, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"The resource identified with \"acct:john@example.com\" does not exist or is not supported by this server."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAnInvalidResourceParameterBasedOnAnEmail(): void
    {
        $client = static::createClient([], [
            'HTTP_HOST' => 'my-service.com',
            'HTTP_PORT' => 443,
        ]);
        $client->request('GET', '/.well-known/webfinger', [
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'john@example.com',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(404, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"The resource identified with \"john@example.com\" does not exist or is not supported by this server."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAnInvalidResourceParameterBasedOnAnUrl(): void
    {
        $client = static::createClient([], [
            'HTTP_HOST' => 'my-service.com',
            'HTTP_PORT' => 443,
        ]);
        $client->request('GET', '/.well-known/webfinger', [
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'https://example.com:8080/+john',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(404, $response->getStatusCode());
        static::assertSame(
            '{"error":"invalid_request","error_description":"The resource identified with \"https://example.com:8080/+john\" does not exist or is not supported by this server."}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAValidResourceParameterBasedOnAnAccount(): void
    {
        $client = static::createClient([], [
            'HTTP_HOST' => 'my-service.com',
            'HTTP_PORT' => 443,
        ]);
        $client->request('GET', '/.well-known/webfinger', [
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'acct:john@my-service.com:443',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertSame(
            '{"subject":"acct:john@my-service.com:443","aliases":["https://my-service.com:443/+john"],"links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://server.example.com"}]}',
            $response->getContent()
        );
    }

    /**
     * @test
     */
    public function aClientSendAnWebFingerRequestWithAValidResourceParameterBasedOnAnUrl(): void
    {
        $client = static::createClient([], [
            'HTTP_HOST' => 'my-service.com',
            'HTTP_PORT' => 443,
        ]);
        $client->request('GET', '/.well-known/webfinger', [
            'rel' => 'http://openid.net/specs/connect/1.0/issuer',
            'resource' => 'https://my-service.com:443/+john',
        ], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();
        static::assertSame(200, $response->getStatusCode());
        static::assertSame(
            '{"subject":"acct:john@my-service.com:443","aliases":["https://my-service.com:443/+john"],"links":[{"rel":"http://openid.net/specs/connect/1.0/issuer","href":"https://server.example.com"}]}',
            $response->getContent()
        );
    }
}
