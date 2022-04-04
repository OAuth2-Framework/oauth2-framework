<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

use League\Uri;
use function League\Uri\build;
use function League\Uri\build_query;
use function League\Uri\parse;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class FragmentResponseMode implements ResponseMode
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct()
    {
        $this->responseFactory = new Psr17Factory();
    }

    public static function create(): static
    {
        return new self();
    }

    public function name(): string
    {
        return ResponseType::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function buildResponse(string $redirectUri, array $data): ResponseInterface
    {
        $uri = parse($redirectUri);
        $data['_'] = '_';
        $uri['fragment'] = build_query($data); //A redirect Uri is not supposed to have fragment so we override it.
        $uri = build($uri);

        $response = $this->responseFactory->createResponse(303);

        return $response->withHeader('Location', $uri);
    }
}
