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

namespace OAuth2Framework\Bundle\Server\Controller;

use Http\Message\MessageFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWSBuilder;
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
     * @param JWKSet     $signatureKeySet
     * @param string     $signatureAlgorithm
     */
    public function enableSignedMetadata(JWSBuilder $jwsBuilder, string $signatureAlgorithm, JWKSet $signatureKeySet)
    {
        $this->metadataEndpoint->enableSignedMetadata($jwsBuilder, $signatureAlgorithm, $signatureKeySet);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->metadataEndpoint->process($request, $handler);
    }
}
