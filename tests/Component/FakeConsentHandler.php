<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentHandler as ConsentHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FakeConsentHandler implements ConsentHandlerInterface
{
    public static function create(): self
    {
        return new self();
    }

    public function handle(ServerRequestInterface $request, string $authorizationId): ResponseInterface
    {
        $response = (new Psr17Factory())->createResponse(303);

        return $response->withHeader('location', 'https://foo.bar/authorization/___ID___/consent');
    }
}
