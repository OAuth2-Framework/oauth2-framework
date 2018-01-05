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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\Command;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\PreConfiguredAuthorizationId;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;

final class CreatePreConfiguredAuthorizationCommand
{
    /**
     * @var PreConfiguredAuthorizationId
     */
    private $preConfiguredAuthorizationId;

    /**
     * @var UserAccountId
     */
    private $userAccountId;

    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var string[]
     */
    private $scopes;

    /**
     * @var null|ResourceServerId
     */
    private $resourceServerId;

    /**
     * CreatePreConfiguredAuthorizationCommand constructor.
     *
     * @param PreConfiguredAuthorizationId $preConfiguredAuthorizationId
     * @param ClientId                     $clientId
     * @param UserAccountId                $userAccountId
     * @param array                        $scopes
     * @param null|ResourceServerId        $resourceServerId
     */
    protected function __construct(PreConfiguredAuthorizationId $preConfiguredAuthorizationId, ClientId $clientId, UserAccountId $userAccountId, array $scopes, ?ResourceServerId $resourceServerId)
    {
        $this->preConfiguredAuthorizationId = $preConfiguredAuthorizationId;
        $this->clientId = $clientId;
        $this->userAccountId = $userAccountId;
        $this->resourceServerId = $resourceServerId;
        $this->scopes = $scopes;
    }

    /**
     * @param PreConfiguredAuthorizationId $preConfiguredAuthorizationId
     * @param ClientId                     $clientId
     * @param UserAccountId                $userAccountId
     * @param array                        $scopes
     * @param null|ResourceServerId        $resourceServerId
     *
     * @return CreatePreConfiguredAuthorizationCommand
     */
    public static function create(PreConfiguredAuthorizationId $preConfiguredAuthorizationId, ClientId $clientId, UserAccountId $userAccountId, array $scopes, ?ResourceServerId $resourceServerId): self
    {
        return new self($preConfiguredAuthorizationId, $clientId, $userAccountId, $scopes, $resourceServerId);
    }

    /**
     * @return PreConfiguredAuthorizationId
     */
    public function getPreConfiguredAuthorizationId(): PreConfiguredAuthorizationId
    {
        return $this->preConfiguredAuthorizationId;
    }

    /**
     * @return UserAccountId
     */
    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @return null|ResourceServerId
     */
    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }
}
