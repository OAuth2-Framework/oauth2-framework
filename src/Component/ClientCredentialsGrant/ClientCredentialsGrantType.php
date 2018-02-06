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

namespace OAuth2Framework\Component\ClientCredentialsGrant;

use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

class ClientCredentialsGrantType implements GrantType
{
    /**
     * @var bool
     */
    private $issueRefreshTokenWithAccessToken = false;

    /**
     * ClientCredentialsGrantType constructor.
     *
     * @param bool $issueRefreshTokenWithAccessToken
     */
    public function __construct(bool $issueRefreshTokenWithAccessToken)
    {
        $this->issueRefreshTokenWithAccessToken = $issueRefreshTokenWithAccessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function associatedResponseTypes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'client_credentials';
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequest(ServerRequestInterface $request)
    {
        // Nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        // Nothing to do
        return $grantTypeData;
    }

    /**
     * {@inheritdoc}
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        $client = $grantTypeData->getClient();
        if ($client->isPublic()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_CLIENT, 'The client is not a confidential client.');
        }

        $grantTypeData = $grantTypeData->withResourceOwnerId($grantTypeData->getClient()->getPublicId());

        return $grantTypeData;
    }

    /**
     * @return bool
     */
    public function isRefreshTokenIssuedWithAccessToken()
    {
        return $this->issueRefreshTokenWithAccessToken;
    }
}
