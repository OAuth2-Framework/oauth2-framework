<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\MetadataEndpoint;

use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MetadataEndpoint implements MiddlewareInterface
{
    private ?JWK $signatureKey = null;

    private ?string $signatureAlgorithm = null;

    private ?JWSBuilder $jwsBuilder = null;

    public function __construct(
        private Metadata $metadata
    ) {
    }

    public function enableSignature(JWSBuilder $jwsBuilder, string $signatureAlgorithm, JWK $signatureKey): static
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->signatureKey = $signatureKey;
        $this->signatureAlgorithm = $signatureAlgorithm;

        return $this;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = $this->metadata->all();
        if ($this->jwsBuilder !== null) {
            $data['signed_metadata'] = $this->sign($data);
        }

        $response = $handler->handle($request);
        $response->getBody()
            ->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ;

        return $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }

    private function sign(array $metadata): string
    {
        $header = [
            'alg' => $this->signatureAlgorithm,
        ];
        if ($this->signatureKey->has('kid')) {
            $header['kid'] = $this->signatureKey->get('kid');
        }
        $jws = $this->jwsBuilder
            ->create()
            ->withPayload(json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->addSignature($this->signatureKey, $header)
            ->build()
        ;
        $serializer = new CompactSerializer();

        return $serializer->serialize($jws, 0);
    }
}
