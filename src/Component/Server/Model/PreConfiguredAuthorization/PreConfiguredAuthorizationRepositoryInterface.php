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

namespace OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization;

use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

interface PreConfiguredAuthorizationRepositoryInterface
{
    /**
     * @param UserAccountId         $userAccountId
     * @param ClientId              $clientId
     * @param string[]              $scopes
     * @param null|ResourceServerId $resourceServerId
     *
     * @return PreConfiguredAuthorization
     */
    public function create(UserAccountId $userAccountId, ClientId $clientId, array $scopes, ?ResourceServerId $resourceServerId): PreConfiguredAuthorization;

    /**
     * @param PreConfiguredAuthorization $preConfiguredAuthorization
     */
    public function save(PreConfiguredAuthorization $preConfiguredAuthorization);

    /**
     * @param UserAccountId         $userAccountId
     * @param ClientId              $clientId
     * @param string[]              $scopes
     * @param null|ResourceServerId $resourceServerId
     *
     * @return PreConfiguredAuthorization|null
     */
    public function find(UserAccountId $userAccountId, ClientId $clientId, array $scopes, ?ResourceServerId $resourceServerId): ?PreConfiguredAuthorization;
}
