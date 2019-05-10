<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\BearerTokenType;

use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class RequestBodyTokenFinder implements TokenFinder
{
    public function find(ServerRequestInterface $request, array &$additionalCredentialValues): ?string
    {
        try {
            $params = RequestBodyParser::parseFormUrlEncoded($request);

            return $params['access_token'] ?? null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
