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

namespace OAuth2Framework\Component\ImplicitGrant;

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class ImplicitGrantType implements GrantType
{
    public function associatedResponseTypes(): array
    {
        return ['token'];
    }

    public function name(): string
    {
        return 'implicit';
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_GRANT, 'The implicit grant type cannot be called from the token endpoint.');
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_GRANT, 'The implicit grant type cannot be called from the token endpoint.');
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_GRANT, 'The implicit grant type cannot be called from the token endpoint.');
    }
}
