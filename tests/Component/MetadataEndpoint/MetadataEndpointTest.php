<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\MetadataEndpoint;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\None;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\Core\Middleware\TerminalRequestHandler;
use OAuth2Framework\Component\MetadataEndpoint\Metadata;
use OAuth2Framework\Component\MetadataEndpoint\MetadataEndpoint;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class MetadataEndpointTest extends OAuth2TestCase
{
    private ?MetadataEndpoint $metadataEndpoint = null;

    /**
     * @test
     */
    public function theMetadataEndpointCanReceiveRegistrationRequests(): void
    {
        $request = $this->buildRequest();

        $response = $this->getMetadataEndpoint()
            ->process($request, new TerminalRequestHandler())
        ;
        $response->getBody()
            ->rewind()
        ;
        $body = $response->getBody()
            ->getContents()
        ;

        if (class_exists(JWSBuilder::class)) {
            static::assertSame('{"foo":"bar","signed_metadata":"eyJhbGciOiJub25lIn0.eyJmb28iOiJiYXIifQ."}', $body);
        } else {
            static::assertSame('{"foo":"bar"}', $body);
        }
        static::assertSame(200, $response->getStatusCode());
        static::assertSame(['application/json; charset=UTF-8'], $response->getHeader('content-type'));
    }

    private function getMetadataEndpoint(): MetadataEndpoint
    {
        if ($this->metadataEndpoint === null) {
            $metadata = new Metadata();
            $metadata->set('foo', 'bar');

            $this->metadataEndpoint = new MetadataEndpoint($metadata);

            if (class_exists(JWSBuilder::class)) {
                $jwsBuilder = new JWSBuilder(new AlgorithmManager([new None()]));
                $key = new JWK([
                    'kty' => 'none',
                ]);
                $this->metadataEndpoint->enableSignature($jwsBuilder, 'none', $key);
            }
        }

        return $this->metadataEndpoint;
    }
}
