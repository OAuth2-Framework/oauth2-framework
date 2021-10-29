<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\BearerTokenType;

use Psr\Http\Message\ServerRequestInterface;

final class QueryStringTokenFinder implements TokenFinder
{
    public function find(ServerRequestInterface $request, array &$additionalCredentialValues): ?string
    {
        $params = $request->getQueryParams();

        return $params['access_token'] ?? null;
    }
}
