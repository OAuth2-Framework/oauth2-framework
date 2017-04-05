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

namespace OAuth2Framework\Component\Server\Command\RefreshToken;

use OAuth2Framework\Component\Server\Command\CommandWithDataTransporter;
use OAuth2Framework\Component\Server\DataTransporter;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;

final class CreateRefreshTokenCommand extends CommandWithDataTransporter
{
    /**
     * @var \DateTimeImmutable|null
     */
    private $expiresAt;

    /**
     * @var ResourceOwnerId
     */
    private $userAccountId;

    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * @var DataBag
     */
    private $metadatas;

    /**
     * @var string[]
     */
    private $scopes;

    /**
     * CreateRefreshTokenCommand constructor.
     *
     * @param ResourceOwnerId         $userAccountId
     * @param ClientId                $clientId
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param string[]                $scopes
     * @param \DateTimeImmutable|null $expiresAt
     * @param DataTransporter|null    $dataTransporter
     */
    protected function __construct(ResourceOwnerId $userAccountId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes, ? \DateTimeImmutable $expiresAt, ? DataTransporter $dataTransporter)
    {
        parent::__construct($dataTransporter);
        $this->expiresAt = $expiresAt;
        $this->userAccountId = $userAccountId;
        $this->clientId = $clientId;
        $this->parameters = $parameters;
        $this->metadatas = $metadatas;
        $this->scopes = $scopes;
    }

    /**
     * @param ResourceOwnerId         $userAccountId
     * @param ClientId                $clientId
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param string[]                $scopes
     * @param \DateTimeImmutable|null $expiresAt
     * @param DataTransporter|null    $dataTransporter
     *
     * @return CreateRefreshTokenCommand
     */
    public static function create(ResourceOwnerId $userAccountId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes, ? \DateTimeImmutable $expiresAt, ? DataTransporter $dataTransporter): CreateRefreshTokenCommand
    {
        return new self($userAccountId, $clientId, $parameters, $metadatas, $scopes, $expiresAt, $dataTransporter);
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getExpiresAt(): ? \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * @return ResourceOwnerId
     */
    public function getResourceOwnerId(): ResourceOwnerId
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
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
