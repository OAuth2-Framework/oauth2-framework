<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\BearerTokenType;

use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class RequestBodyTokenFinder implements TokenFinder
{
    public static function create(): static
    {
        return new self();
    }

    public function find(ServerRequestInterface $request, array &$additionalCredentialValues): ?string
    {
        try {
            return RequestBodyParser::parseFormUrlEncoded($request)->get('access_token');
        } catch (Throwable) {
            return null;
        }
    }
}
