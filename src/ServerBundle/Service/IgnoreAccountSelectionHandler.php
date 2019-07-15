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

namespace OAuth2Framework\ServerBundle\Service;

use OAuth2Framework\Component\AuthorizationEndpoint\SelectAccountHandler as SelectAccountHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IgnoreAccountSelectionHandler implements SelectAccountHandlerInterface
{
    public function handle(ServerRequestInterface $request, string $authorizationId): ?ResponseInterface
    {
        return null;
    }
}
