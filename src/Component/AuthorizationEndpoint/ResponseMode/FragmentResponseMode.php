<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

use League\Uri\Components\Query;
use League\Uri\Uri;
use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class FragmentResponseMode implements ResponseMode
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
        return ResponseType::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function buildResponse(string $redirectUri, array $data): ResponseInterface
    {
        $fragment = Query::createFromParams($data)
            ->append('_=_')
        ;

        $uri = Uri::createFromString($redirectUri)
            ->withFragment($fragment) //A redirect Uri is not supposed to have fragment so we override it.
        ;

        return $this->responseFactory->createResponse(303)
            ->withHeader('Location', $uri->toString())
        ;
    }
}
