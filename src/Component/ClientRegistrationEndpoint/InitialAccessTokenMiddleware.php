<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRegistrationEndpoint;

use InvalidArgumentException;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class InitialAccessTokenMiddleware implements MiddlewareInterface
{
    public function __construct(
        private BearerToken $bearerToken,
        private InitialAccessTokenRepository $initialAccessTokenRepository,
        private bool $isRequired
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $values = [];
            $token = $this->bearerToken->find($request, $values);
            if ($token === null) {
                if (! $this->isRequired) {
                    return $handler->handle($request);
                }

                throw new InvalidArgumentException('Initial Access Token is missing or invalid.');
            }

            $initialAccessToken = $this->initialAccessTokenRepository->find(InitialAccessTokenId::create($token));

            if ($initialAccessToken === null || $initialAccessToken->isRevoked()) {
                throw new InvalidArgumentException('Initial Access Token is missing or invalid.');
            }
            if ($initialAccessToken->hasExpired()) {
                throw new InvalidArgumentException('Initial Access Token expired.');
            }

            $request = $request->withAttribute('initial_access_token', $initialAccessToken);
        } catch (InvalidArgumentException $e) {
            throw OAuth2Error::invalidRequest($e->getMessage(), [], $e);
        }

        return $handler->handle($request);
    }
}
