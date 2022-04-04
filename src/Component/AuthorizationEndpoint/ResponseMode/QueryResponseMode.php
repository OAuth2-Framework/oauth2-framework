<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

use League\Uri\Components\Query;
use League\Uri\Uri;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class QueryResponseMode implements ResponseMode
{
    private readonly ResponseFactoryInterface $responseFactory;

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
        $uri = Uri::createFromString($redirectUri);
        $query = Query::createFromParams($data);
        if ($uri->getQuery() !== null) {
            $query = $query->merge($uri->getQuery());
        }
        $uri = $uri
            ->withQuery($query)
            ->withFragment('_=_') //A redirect Uri is not supposed to have fragments, so we override it.
        ;

        return $this->responseFactory->createResponse(303)
            ->withHeader('Location', $uri->toString())
        ;
    }
}
