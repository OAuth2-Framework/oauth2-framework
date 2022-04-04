<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Controller;

use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\MetadataEndpoint\MetadataEndpoint;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MetadataController implements MiddlewareInterface
{
    private readonly MetadataEndpoint $metadataEndpoint;

    public function __construct(MetadataBuilder $metadataBuilder)
    {
        $metadata = $metadataBuilder->getMetadata();
        $this->metadataEndpoint = new MetadataEndpoint($metadata);
    }

    public function enableSignedMetadata(JWSBuilder $jwsBuilder, string $signatureAlgorithm, JWK $signatureKey): static
    {
        $this->metadataEndpoint->enableSignature($jwsBuilder, $signatureAlgorithm, $signatureKey);

        return $this;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->metadataEndpoint->process($request, $handler);
    }
}
