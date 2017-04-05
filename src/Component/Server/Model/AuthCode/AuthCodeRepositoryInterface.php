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

namespace OAuth2Framework\Component\Server\Model\AuthCode;

use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

interface AuthCodeRepositoryInterface
{
    /**
     * @param ClientId                $clientId
     * @param UserAccountId           $userAccountId
     * @param array                   $queryParameters
     * @param string                  $redirectUri
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param bool                    $withRefreshToken
     * @param null|ResourceServerId   $resourceServerId
     * @param null|\DateTimeImmutable $expiresAt
     *
     * @return AuthCode
     */
    public function create(ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, DataBag $parameters, DataBag $metadatas, array $scopes, bool $withRefreshToken, ? ResourceServerId $resourceServerId, ? \DateTimeImmutable $expiresAt): AuthCode;

    /**
     * @param AuthCode $authCode
     */
    public function save(AuthCode $authCode);

    /**
     * Retrieve the stored data for the given authorization code.
     *
     * @param AuthCodeId $authCodeId the authorization code string for which to fetch data
     *
     * @return null|AuthCode
     *
     * @see     http://tools.ietf.org/html/rfc6749#section-4.1
     */
    public function find(AuthCodeId $authCodeId): ? AuthCode;
}
