<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRegistrationEndpoint;

use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class ClientRegistrationEndpointTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function theClientRegistrationEndpointCanReceiveRegistrationRequests(): void
    {
        $request = $this->buildRequest(method: 'POST', contentType: 'application/json');

        $response = $this->getClientRegistrationEndpoint()
            ->process($request)
        ;

        static::assertSame(201, $response->getStatusCode());
        $response->getBody()
            ->rewind()
        ;
        static::assertMatchesRegularExpression('/^{"client_id":"\w+"}$/', $response->getBody()->getContents());
    }
}
