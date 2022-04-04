<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

use function array_key_exists;
use League\Uri;
use function League\Uri\build;
use function League\Uri\build_query;
use function League\Uri\parse;
use function League\Uri\parse_query;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class QueryResponseMode implements ResponseMode
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
        return ResponseType::RESPONSE_TYPE_MODE_QUERY;
    }

    public function buildResponse(string $redirectUri, array $data): ResponseInterface
    {
        $uri = parse($redirectUri);
        if (array_key_exists('query', $uri) && $uri['query'] !== null) {
            $query = parse_query($uri['query']);
            $data = array_merge($query, $data);
        }
        $uri['query'] = build_query($data);
        $uri['fragment'] = '_=_'; //A redirect Uri is not supposed to have fragments, so we override it.
        $uri = build($uri);

        $response = $this->responseFactory->createResponse(303);

        return $response->withHeader('Location', $uri);
    }
}
