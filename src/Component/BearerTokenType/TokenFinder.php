<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\BearerTokenType;

use Psr\Http\Message\ServerRequestInterface;

interface TokenFinder
{
    public function find(ServerRequestInterface $request, array &$additionalCredentialValues): ?string;
}
