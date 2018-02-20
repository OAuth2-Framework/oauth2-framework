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

namespace OAuth2Framework\Component\Server\Command\PreConfiguredAuthorization;

use OAuth2Framework\Component\Server\Command\CommandWithDataTransporter;
use OAuth2Framework\Component\Server\DataTransporter;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class RevokePreConfiguredAuthorizationCommand extends CommandWithDataTransporter
{
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
     * RevokePreConfiguredAuthorizationCommand constructor.
     *
     * @param ClientId              $clientId
     * @param UserAccountId         $userAccountId
     * @param array                 $scopes
     * @param null|ResourceServerId $resourceServerId
     * @param DataTransporter|null  $dataTransporter
     */
    protected function __construct(ClientId $clientId, UserAccountId $userAccountId, array $scopes, ?ResourceServerId $resourceServerId, ? DataTransporter $dataTransporter)
    {
        parent::__construct($dataTransporter);
        $this->clientId = $clientId;
        $this->userAccountId = $userAccountId;
        $this->resourceServerId = $resourceServerId;
        $this->scopes = $scopes;
    }

    /**
     * @param ClientId              $clientId
     * @param UserAccountId         $userAccountId
     * @param array                 $scopes
     * @param null|ResourceServerId $resourceServerId
     * @param null|DataTransporter  $dataTransporter
     *
     * @return RevokePreConfiguredAuthorizationCommand
     */
    public static function create(ClientId $clientId, UserAccountId $userAccountId, array $scopes, ?ResourceServerId $resourceServerId, ? DataTransporter $dataTransporter): self
    {
        return new self($clientId, $userAccountId, $scopes, $resourceServerId, $dataTransporter);
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
