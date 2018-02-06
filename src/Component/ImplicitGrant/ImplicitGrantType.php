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

use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

class ImplicitGrantType implements GrantType
{
    /**
     * {@inheritdoc}
     */
    public function associatedResponseTypes(): array
    {
        return ['token'];
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'implicit';
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequest(ServerRequestInterface $request)
    {
        throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The implicit grant type cannot be called from the token endpoint.');
    }

    /**
     * {@inheritdoc}
     */
    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The implicit grant type cannot be called from the token endpoint.');
    }

    /**
     * {@inheritdoc}
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The implicit grant type cannot be called from the token endpoint.');
    }
}
