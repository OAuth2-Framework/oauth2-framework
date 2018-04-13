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

namespace OAuth2Framework\ServerBundle\Controller;

use Http\Message\MessageFactory;
use Jose\Component\Core\JWK;
use OAuth2Framework\Component\MetadataEndpoint\MetadataEndpoint;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MetadataController implements MiddlewareInterface
{
    /**
     * @var MetadataEndpoint
     */
    private $metadataEndpoint;

    /**
     * MetadataController constructor.
     *
     * @param MessageFactory  $messageFactory
     * @param MetadataBuilder $metadataBuilder
     */
    public function __construct(MessageFactory $messageFactory, MetadataBuilder $metadataBuilder)
    {
        $metadata = $metadataBuilder->getMetadata();
        $this->metadataEndpoint = new MetadataEndpoint($messageFactory, $metadata);
    }

    /**
     * @param JWSBuilder $jwsBuilder
     * @param JWK        $signatureKey
     * @param string     $signatureAlgorithm
     */
    public function enableSignedMetadata(JWSBuilder $jwsBuilder, string $signatureAlgorithm, JWK $signatureKey)
    {
        $this->metadataEndpoint->enableSignature($jwsBuilder, $signatureAlgorithm, $signatureKey);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->metadataEndpoint->process($request, $handler);
    }
}
