<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\MetadataEndpoint\Tests;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\None;
use Jose\Component\Signature\JWSBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\MetadataEndpoint\Metadata;
use OAuth2Framework\Component\MetadataEndpoint\MetadataEndpoint;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group MetadataEndpoint
 *
 * @internal
 */
final class MetadataEndpointTest extends TestCase
{
    /**
     * @var null|MetadataEndpoint
     */
    private $metadataEndpoint;

    /**
     * @var null|ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @test
     */
    public function theMetadataEndpointCanReceiveRegistrationRequests()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $handler = $this->prophesize(RequestHandlerInterface::class);

        $response = $this->getMetadataEndpoint()->process($request->reveal(), $handler->reveal());
        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();

        if (class_exists(JWSBuilder::class)) {
            static::assertEquals('{"foo":"bar","signed_metadata":"eyJhbGciOiJub25lIn0.eyJmb28iOiJiYXIifQ."}', $body);
        } else {
            static::assertEquals('{"foo":"bar"}', $body);
        }
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals(['application/json; charset=UTF-8'], $response->getHeader('content-type'));
    }

    private function getMetadataEndpoint(): MetadataEndpoint
    {
        if (null === $this->metadataEndpoint) {
            $metadata = new Metadata();
            $metadata->set('foo', 'bar');

            $this->metadataEndpoint = new MetadataEndpoint(
                $this->getResponseFactory(),
                $metadata
            );

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

    private function getResponseFactory(): ResponseFactoryInterface
    {
        if (null === $this->responseFactory) {
            $this->responseFactory = new Psr17Factory();
        }

        return $this->responseFactory;
    }
}
