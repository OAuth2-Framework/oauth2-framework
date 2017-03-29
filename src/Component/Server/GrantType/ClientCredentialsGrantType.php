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

namespace OAuth2Framework\Component\Server\GrantType;

use OAuth2Framework\Component\Server\Endpoint\Token\GrantTypeData;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class ClientCredentialsGrantType implements GrantTypeInterface
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
    public function prepareTokenResponse(ServerRequestInterface $request, GrantTypeData $grantTypeResponse): GrantTypeData
    {
        // Nothing to do
        return $grantTypeResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeResponse): GrantTypeData
    {
        $client = $grantTypeResponse->getClient();
        if ($client->isPublic()) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_CLIENT, 'error_description' => 'The client is not a confidential client.']);
        }

        if (true === $this->isRefreshTokenIssuedWithAccessToken()) {
            $grantTypeResponse = $grantTypeResponse->withRefreshToken();
        } else {
            $grantTypeResponse = $grantTypeResponse->withoutRefreshToken();
        }

        $grantTypeResponse = $grantTypeResponse->withResourceOwnerId($grantTypeResponse->getClient()->getPublicId());

        return $grantTypeResponse;
    }

    /**
     * @return bool
     */
    public function isRefreshTokenIssuedWithAccessToken()
    {
        return $this->issueRefreshTokenWithAccessToken;
    }
}
