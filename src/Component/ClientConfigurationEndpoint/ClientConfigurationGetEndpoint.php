<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientConfigurationEndpoint;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ClientConfigurationGetEndpoint implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $client = $request->getAttribute('client');
        $response = $this->responseFactory->createResponse();
        $response->getBody()
            ->write(json_encode($client->all(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ;
        $headers = [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private',
            'Pragma' => 'no-cache',
        ];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }
}
