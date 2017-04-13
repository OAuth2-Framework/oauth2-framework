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

namespace OAuth2Framework\Bundle\Server\Controller;

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Jose\Object\JWKSetInterface;
use Jose\SignerInterface;
use OAuth2Framework\Bundle\Server\Service\MetadataBuilder;
use OAuth2Framework\Component\Server\Endpoint\Metadata\MetadataEndpoint;
use Psr\Http\Message\ServerRequestInterface;

class MetadataController implements MiddlewareInterface
{
    /**
     * @var MetadataEndpoint
     */
    private $metadataEndpoint;

    /**
     * MetadataController constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param MetadataBuilder          $metadataBuilder
     */
    public function __construct(ResponseFactoryInterface $responseFactory, MetadataBuilder $metadataBuilder)
    {
        $metadata = $metadataBuilder->getMetadata();
        $this->metadataEndpoint = new MetadataEndpoint($responseFactory, $metadata);
    }

    /**
     * @param SignerInterface $signer
     * @param JWKSetInterface $signatureKeySet
     * @param string          $signatureAlgorithm
     */
    public function enableSignedMetadata(SignerInterface $signer, string $signatureAlgorithm, JWKSetInterface $signatureKeySet)
    {
        $this->metadataEndpoint->enableSignedMetadata($signer, $signatureAlgorithm, $signatureKeySet);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        return $this->metadataEndpoint->process($request, $delegate);
    }
}
