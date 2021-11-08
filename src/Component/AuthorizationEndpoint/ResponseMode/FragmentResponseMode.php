<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

use League\Uri;
use function League\Uri\build;
use function League\Uri\build_query;
use function League\Uri\parse;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use Psr\Http\Message\ResponseInterface;

final class FragmentResponseMode implements ResponseMode
{
    public static function create(): self
    {
        return new self();
    }

    public function name(): string
    {
        return ResponseType::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function buildResponse(ResponseInterface $response, string $redirectUri, array $data): ResponseInterface
    {
        $uri = parse($redirectUri);
        $data['_'] = '_';
        $uri['fragment'] = build_query($data); //A redirect Uri is not supposed to have fragment so we override it.
        $uri = build($uri);

        $response = $response->withStatus(303);

        return $response->withHeader('Location', $uri);
    }
}
