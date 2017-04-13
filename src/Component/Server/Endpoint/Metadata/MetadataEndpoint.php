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
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Jose\Factory\JWSFactory;
use Jose\SignerInterface;
use Jose\Object\JWKSetInterface;
use Psr\Http\Message\ServerRequestInterface;

final class MetadataEndpoint implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var null|JWKSetInterface
     */
    private $signatureKeySet = null;

    /**
     * @var null|string
     */
    private $signatureAlgorithm = null;

    /**
     * @var null|SignerInterface
     */
    private $signer = null;

    /**
     * MetadataEndpoint constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param Metadata                 $metadata
     */
    public function __construct(ResponseFactoryInterface $responseFactory, Metadata $metadata)
    {
        $this->responseFactory = $responseFactory;
        $this->metadata = $metadata;
    }

    /**
     * @param SignerInterface $signer
     * @param JWKSetInterface $signatureKeySet
     * @param string          $signatureAlgorithm
     */
    public function enableSignedMetadata(SignerInterface $signer, string $signatureAlgorithm, JWKSetInterface $signatureKeySet)
    {
        $this->signer = $signer;
        $this->signatureKeySet = $signatureKeySet;
        $this->signatureAlgorithm = $signatureAlgorithm;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $data = $this->metadata->jsonSerialize();
        if ($this->isSignedMetadataEnabled()) {
            $data['signed_metadata'] = $this->signMetadata($data);
        }
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(json_encode($data));
        $response = $response->withHeader('Content-Type', 'application/json; charset=UTF-8');

        return $response;
    }

    /**
     * @return bool
     */
    private function isSignedMetadataEnabled(): bool
    {
        return null !== $this->signer;
    }

    /**
     * @param array $metadata
     *
     * @return string
     */
    private function signMetadata(array $metadata): string
    {
        $headers = [
            'alg' => $this->signatureAlgorithm,
        ];
        $key = $this->signatureKeySet->selectKey('sig', $this->signatureAlgorithm);
        Assertion::notNull($key, sprintf('Unable to find a signed key for the algorithm \'%s\'.', $this->signatureAlgorithm));
        $jws = JWSFactory::createJWS($metadata);
        $jws = $jws->addSignatureInformation($key, $headers);
        $this->signer->sign($jws);

        return $jws->toCompactJSON(0);
    }
}
