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

namespace OAuth2Framework\Component\Server\ImplicitGrant;

use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

final class ImplicitGrantType implements GrantType
{
    /**
     * {@inheritdoc}
     */
    public function getAssociatedResponseTypes(): array
    {
        return ['token'];
    }

    /**
     * {@inheritdoc}
     */
    public function getGrantType(): string
    {
        return 'implicit';
    }

    /**
     * {@inheritdoc}
     */
    public function checkTokenRequest(ServerRequestInterface $request)
    {
        throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The implicit grant type cannot be called from the token endpoint.');
    }

    /**
     * {@inheritdoc}
     */
    public function prepareTokenResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
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
