<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\BearerTokenType;

use Psr\Http\Message\ServerRequestInterface;

final class AuthorizationHeaderTokenFinder implements TokenFinder
{
    public static function create(): static
    {
        return new self();
    }

    public function find(ServerRequestInterface $request, array &$additionalCredentialValues): ?string
    {
        $authorizationHeaders = $request->getHeader('AUTHORIZATION');

        foreach ($authorizationHeaders as $header) {
            if (preg_match('/' . preg_quote('Bearer', '/') . '\s([a-zA-Z0-9\-_\+~\/\.]+)/', $header, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }
}
