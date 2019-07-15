<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\BearerTokenType;

use Psr\Http\Message\ServerRequestInterface;
use function Safe\preg_match;

final class AuthorizationHeaderTokenFinder implements TokenFinder
{
    public function find(ServerRequestInterface $request, array &$additionalCredentialValues): ?string
    {
        $authorizationHeaders = $request->getHeader('AUTHORIZATION');

        foreach ($authorizationHeaders as $header) {
            if (1 === preg_match('/'.preg_quote('Bearer', '/').'\s([a-zA-Z0-9\-_\+~\/\.]+)/', $header, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
