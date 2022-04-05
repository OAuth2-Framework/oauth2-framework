<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\TokenType;

use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class TokenTypeMiddleware.
 *
 * This middleware should be used with the Token Endpoint.
 */
final class TokenTypeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TokenTypeManager $tokenTypeManager,
        private bool $tokenTypeParameterAllowed
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $tokenType = $this->findTokenType($request);
        $request = $request->withAttribute('token_type', $tokenType);

        return $handler->handle($request);
    }

    private function findTokenType(ServerRequestInterface $request): TokenType
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        if ($this->tokenTypeParameterAllowed === true && $parameters->has('token_type')) {
            return $this->tokenTypeManager->get($parameters->get('token_type'));
        }

        return $this->tokenTypeManager->getDefault();
    }
}
