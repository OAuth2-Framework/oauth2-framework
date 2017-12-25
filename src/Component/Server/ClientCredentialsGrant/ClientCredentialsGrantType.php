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

namespace OAuth2Framework\Component\Server\ClientCredentialsGrant;

use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

final class ClientCredentialsGrantType implements GrantType
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
    public function getAssociatedResponseTypes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getGrantType(): string
    {
        return 'client_credentials';
    }

    /**
     * {@inheritdoc}
     */
    public function checkTokenRequest(ServerRequestInterface $request)
    {
        // Nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function prepareTokenResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
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
