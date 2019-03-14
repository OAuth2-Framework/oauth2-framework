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

namespace OAuth2Framework\Component\MetadataEndpoint\Tests;

use Http\Message\MessageFactory\DiactorosMessageFactory;
use Http\Message\ResponseFactory;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\None;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\MetadataEndpoint\Metadata;
use OAuth2Framework\Component\MetadataEndpoint\MetadataEndpoint;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @group MetadataEndpoint
 */
final class MetadataEndpointTest extends TestCase
{
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

        if (\class_exists(JWSBuilder::class)) {
            static::assertEquals('{"foo":"bar","signed_metadata":"eyJhbGciOiJub25lIn0.eyJmb28iOiJiYXIifQ."}', $body);
        } else {
            static::assertEquals('{"foo":"bar"}', $body);
        }
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals(['application/json; charset=UTF-8'], $response->getHeader('content-type'));
    }

    /**
     * @var MetadataEndpoint|null
     */
    private $metadataEndpoint = null;

    private function getMetadataEndpoint(): MetadataEndpoint
    {
        if (null === $this->metadataEndpoint) {
            $metadata = new Metadata();
            $metadata->set('foo', 'bar');

            $this->metadataEndpoint = new MetadataEndpoint(
                $this->getResponseFactory(),
                $metadata
            );

            if (\class_exists(JWSBuilder::class)) {
                $jwsBuilder = new JWSBuilder(new StandardConverter(), AlgorithmManager::create([new None()]));
                $key = JWK::create([
                    'kty' => 'none',
                ]);
                $this->metadataEndpoint->enableSignature($jwsBuilder, 'none', $key);
            }
        }

        return $this->metadataEndpoint;
    }

    /**
     * @var ResponseFactory|null
     */
    private $responseFactory = null;

    private function getResponseFactory(): ResponseFactory
    {
        if (null === $this->responseFactory) {
            $this->responseFactory = new DiactorosMessageFactory();
        }

        return $this->responseFactory;
    }
}
