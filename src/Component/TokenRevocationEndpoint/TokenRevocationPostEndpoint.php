<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\TokenRevocationEndpoint;

use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class TokenRevocationPostEndpoint extends TokenRevocationEndpoint
{
    /**
     * {@inheritdoc}
     */
    protected function getRequestParameters(ServerRequestInterface $request): array
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);

        return \array_intersect_key($parameters, \array_flip(['token', 'token_type_hint']));
    }
}
