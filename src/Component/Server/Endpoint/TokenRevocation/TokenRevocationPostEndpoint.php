<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Endpoint\TokenRevocation;

use Psr\Http\Message\ServerRequestInterface;

final class TokenRevocationPostEndpoint extends TokenRevocationEndpoint
{
    /**
     * {@inheritdoc}
     */
    protected function getRequestParameters(ServerRequestInterface $request): array
    {
        $parameters = $request->getParsedBody() ?? [];

        return array_intersect_key($parameters, array_flip(['token', 'token_type_hint']));
    }
}
