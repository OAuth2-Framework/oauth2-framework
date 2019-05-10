<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

interface AuthorizationCodeRepository
{
    /**
     * Retrieve the stored data for the given authorization code.
     *
     * @param AuthorizationCodeId $authorizationCodeId the authorization code string for which to fetch data
     *
     * @see     http://tools.ietf.org/html/rfc6749#section-4.1
     */
    public function find(AuthorizationCodeId $authorizationCodeId): ?AuthorizationCode;

    public function save(AuthorizationCode $authorizationCode): void;

    public function create(ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId): AuthorizationCode;
}
