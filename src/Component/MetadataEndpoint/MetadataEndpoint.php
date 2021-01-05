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

namespace OAuth2Framework\Component\MetadataEndpoint;

use function Safe\json_encode;
use Jose\Component\Core\JWK;
use Jose\Component\Core\Util\JsonConverter;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MetadataEndpoint implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    private Metadata $metadata;

    private ?JWK $signatureKey;

    private ?string $signatureAlgorithm;

    private ?JWSBuilder $jwsBuilder;

    /**
     * MetadataEndpoint constructor.
     */
    public function __construct(ResponseFactoryInterface $responseFactory, Metadata $metadata)
    {
        $this->responseFactory = $responseFactory;
        $this->metadata = $metadata;
    }

    public function enableSignature(JWSBuilder $jwsBuilder, string $signatureAlgorithm, JWK $signatureKey): void
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->signatureKey = $signatureKey;
        $this->signatureAlgorithm = $signatureAlgorithm;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = $this->metadata->all();
        if (null !== $this->jwsBuilder) {
            $data['signed_metadata'] = $this->sign($data);
        }
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }

    private function sign(array $metadata): string
    {
        $header = [
            'alg' => $this->signatureAlgorithm,
        ];
        $jws = $this->jwsBuilder
            ->create()
            ->withPayload(JsonConverter::encode($metadata))
            ->addSignature($this->signatureKey, $header)
            ->build()
        ;
        $serializer = new CompactSerializer();

        return $serializer->serialize($jws, 0);
    }
}
