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

namespace OAuth2Framework\Component\Server\Endpoint\Metadata;

use Assert\Assertion;
use Http\Message\MessageFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Psr\Http\Message\ServerRequestInterface;

final class MetadataEndpoint implements MiddlewareInterface
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var null|JWKSet
     */
    private $signatureKeySet = null;

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
     * @param MessageFactory $messageFactory
     * @param Metadata       $metadata
     */
    public function __construct(MessageFactory $messageFactory, Metadata $metadata)
    {
        $this->messageFactory = $messageFactory;
        $this->metadata = $metadata;
    }

    /**
     * @param JWSBuilder $jwsBuilder
     * @param JWKSet     $signatureKeySet
     * @param string     $signatureAlgorithm
     */
    public function enableSignedMetadata(JWSBuilder $jwsBuilder, string $signatureAlgorithm, JWKSet $signatureKeySet)
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->signatureKeySet = $signatureKeySet;
        $this->signatureAlgorithm = $signatureAlgorithm;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler)
    {
        $data = $this->metadata->jsonSerialize();
        if ($this->isSignedMetadataEnabled()) {
            $data['signed_metadata'] = $this->signMetadata($data);
        }
        $response = $this->messageFactory->createResponse();
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
        $signatureAlgorithm = $this->jwsBuilder->getSignatureAlgorithmManager()->get($this->signatureAlgorithm);
        $headers = [
            'alg' => $this->signatureAlgorithm,
        ];
        $key = $this->signatureKeySet->selectKey('sig', $signatureAlgorithm);
        Assertion::notNull($key, sprintf('Unable to find a signed key for the algorithm \'%s\'.', $this->signatureAlgorithm));
        $jws = $this->jwsBuilder
            ->create()
            ->withPayload($metadata)
            ->addSignature($key, $headers)
            ->build();
        $serializer = new CompactSerializer();
        $assertion = $serializer->serialize($jws, 0);

        return $assertion;
    }
}
