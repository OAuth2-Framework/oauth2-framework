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

namespace OAuth2Framework\Component\Core\AccessToken\Command;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class CreateAccessTokenCommand
{
    /**
     * @var AccessTokenId
     */
    private $accessTokenId;

    /**
     * @var \DateTimeImmutable
     */
    private $expiresAt;

    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var UserAccountId
     */
    private $userAccountId;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * @var DataBag
     */
    private $metadatas;

    /**
     * @var null|ResourceServerId
     */
    private $resourceServerId;

    /**
     * CreateAccessTokenCommand constructor.
     *
     * @param AccessTokenId         $accessTokenId
     * @param ClientId              $clientId
     * @param UserAccountId         $userAccountId
     * @param \DateTimeImmutable    $expiresAt
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param null|ResourceServerId $resourceServerId
     */
    protected function __construct(AccessTokenId $accessTokenId, ClientId $clientId, UserAccountId $userAccountId, \DateTimeImmutable $expiresAt, DataBag $parameters, DataBag $metadatas, ?ResourceServerId $resourceServerId)
    {
        $this->accessTokenId = $accessTokenId;
        $this->clientId = $clientId;
        $this->userAccountId = $userAccountId;
        $this->expiresAt = $expiresAt;
        $this->parameters = $parameters;
        $this->metadatas = $metadatas;
        $this->resourceServerId = $resourceServerId;
    }

    /**
     * @param AccessTokenId         $accessTokenId
     * @param ClientId              $clientId
     * @param UserAccountId         $userAccountId
     * @param \DateTimeImmutable    $expiresAt
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param null|ResourceServerId $resourceServerId
     *
     * @return CreateAccessTokenCommand
     */
    public static function create(AccessTokenId $accessTokenId, ClientId $clientId, UserAccountId $userAccountId, \DateTimeImmutable $expiresAt, DataBag $parameters, DataBag $metadatas, ?ResourceServerId $resourceServerId): self
    {
        return new self($accessTokenId, $clientId, $userAccountId, $expiresAt, $parameters, $metadatas, $resourceServerId);
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return UserAccountId
     */
    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    /**
     * @return DataBag
     */
    public function getParameters(): DataBag
    {
        return $this->parameters;
    }

    /**
     * @return DataBag
     */
    public function getMetadatas(): DataBag
    {
        return $this->metadatas;
    }

    /**
     * @return null|ResourceServerId
     */
    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }

    /**
     * @return AccessTokenId
     */
    public function getAccessTokenId(): AccessTokenId
    {
        return $this->accessTokenId;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
