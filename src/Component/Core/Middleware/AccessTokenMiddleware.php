<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Middleware;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AccessTokenMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TokenTypeManager $tokenTypeManager,
        private AccessTokenRepository $accessTokenRepository
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $additional_credential_values = [];
        $token = $this->tokenTypeManager->findToken($request, $additional_credential_values, $type);
        if ($token !== null) {
            $tokenId = new AccessTokenId($token);
            $accessToken = $this->accessTokenRepository->find($tokenId);
            if ($accessToken === null || $type->isRequestValid(
                $accessToken,
                $request,
                $additional_credential_values
            ) === false) {
                throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_TOKEN, 'Invalid access token.');
            }
            $request = $request->withAttribute('access_token', $accessToken);
        }

        return $handler->handle($request);
    }
}
