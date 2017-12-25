<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\MetadataEndpoint;

use Http\Message\ResponseFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class MetadataEndpoint implements MiddlewareInterface
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var null|JWK
     */
    private $signatureKey = null;

    /**
     * @var null|string
     */
    private $signatureAlgorithm = null;

    /**
     * @var null|JWSBuilder
     */
    private $jwsBuilder = null;

    /**
     * MetadataEndpoint constructor.
     *
     * @param ResponseFactory $responseFactory
     * @param Metadata        $metadata
     */
    public function __construct(ResponseFactory $responseFactory, Metadata $metadata)
    {
        $this->responseFactory = $responseFactory;
        $this->metadata = $metadata;
    }

    /**
     * @param JWSBuilder $jwsBuilder
     * @param JWK        $signatureKey
     * @param string     $signatureAlgorithm
     */
    public function enableSignedMetadata(JWSBuilder $jwsBuilder, string $signatureAlgorithm, JWK $signatureKey)
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->signatureKey = $signatureKey;
        $this->signatureAlgorithm = $signatureAlgorithm;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = $this->metadata->jsonSerialize();
        if ($this->isSignedMetadataEnabled()) {
            $data['signed_metadata'] = $this->signMetadata($data);
        }
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $response = $response->withHeader('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    /**
     * @return bool
     */
    private function isSignedMetadataEnabled(): bool
    {
        return null !== $this->jwsBuilder;
    }

    /**
     * @param array $metadata
     *
     * @return string
     */
    private function signMetadata(array $metadata): string
    {
        $jsonConverter = new StandardConverter();
        $header = [
            'alg' => $this->signatureAlgorithm,
        ];
        $jws = $this->jwsBuilder
            ->create()
            ->withPayload($jsonConverter->encode($metadata))
            ->addSignature($this->signatureKey, $header)
            ->build();
        $serializer = new CompactSerializer($jsonConverter);
        $assertion = $serializer->serialize($jws, 0);

        return $assertion;
    }
}
